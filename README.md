## PensoPay_Magento2

Module PensoPay\Payment implements integration with the PensoPay payment service provider.

Currently in beta release, use at your own risk. Pull requests welcome!

Tested in Magento 2.1.2 - 2.3

Implemented so far:
* Authorize
* Capture 
* Partial Capture
* Refund
* Partial Refund
* Cancel
* Payment Fees

### Installation
```
composer require pensopay/magento2
php bin/magento module:enable PensoPay_Payment
php bin/magento setup:upgrade
php bin/magento setup:di:compile
``` 

**Please note that FTP installation will not work as this module has requirements that will be auto installed when using composer**
