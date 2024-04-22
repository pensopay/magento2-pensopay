<?php
namespace PensoPay\Payment\Model\Ui\Method;

/**
 * Class PayPalConfigProvider
 */
final class PayPalConfigProvider extends \PensoPay\Payment\Model\Ui\ConfigProvider
{
    const CODE = 'pensopay_paypal';
    protected string $_code = self::CODE;
}
