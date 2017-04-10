Zorn_AutoInvoice
===================
AutoInvoice automatically sends an invoice when payment is received.

Installation
------------

### Via composer

Please go to the Magento2 root directory and run the following commands in the shell:

```
composer config repositories.zorn_autoinvoice vcs git@github.com:zsoerenm/magento2-autoinvoice.git
composer require zsoerenm/magento2-autoinvoice
bin/magento module:enable Zorn_AutoInvoice
bin/magento setup:upgrade
```

Uninstall
------------

```
bin/magento module:uninstall Zorn_AutoInvoice
bin/magento setup:upgrade
```

Copyright
---------
(c) 2017 Sören Zorn
