## PensoPay_Magento2

Module PensoPay\Payment implements integration with the pensopay payment service provider.

Tested in Magento 2.1.2 - 2.4.6

Module supports:
* Authorize
* Capture 
* Partial Capture
* Refund
* Partial Refund
* Cancel
* Payment Fees
* Multiple Stores with Multiple Accounts

### Installation
```
composer require pensopay/magento2
php bin/magento module:enable PensoPay_Payment
php bin/magento setup:upgrade
php bin/magento setup:di:compile
``` 

**Please note that FTP installation will not work as this module has requirements that will be auto installed when using composer**
