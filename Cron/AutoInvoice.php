<?php
namespace Zorn\AutoInvoice\Cron;

use \Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Registry;
use Magento\Framework\Locale\ResolverInterface;

class AutoInvoice
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\State $state
     * @param \Psr\Log\LoggerInterface $logger
     * @param Registry $registry
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\State $state,
        \Psr\Log\LoggerInterface $logger,
        Registry $registry
    )
    {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_objectManager = $objectManager;
        $this->_state = $state;
        $this->_logger = $logger;
        $this->registry = $registry;
    }

    public function autoInvoice()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $salesOrderCollection */
        $salesOrderCollection = $this->_orderCollectionFactory->create();
        $salesOrderCollection->addFieldToFilter('status', 'Processing');
        $orders = $salesOrderCollection->load();
        /** @var \Magento\Sales\Model\Order $order */
        foreach($orders as $order) {
            if(!$order->hasInvoices() && $order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $this->registry->register('current_invoice', $invoice);
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(true);
                $invoice->getOrder()->setIsInProcess(true);

                /** @var \Magento\Framework\DB\Transaction $transactionSave */
                $transactionSave = $this->_objectManager->create(
                    'Magento\Framework\DB\Transaction'
                )->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();

                try {
                    $this->_invoiceSender->send($invoice, true);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }

                $order->addStatusHistoryComment(
                    __('Automatically notified customer about invoice #%1.', $invoice->getId())
                )
                    ->setIsCustomerNotified(true)
                    ->save();

            }
        }
    }
}