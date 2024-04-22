<?php
namespace PensoPay\Payment\Model\Ui\Method;

/**
 * Class MobilePayConfigProvider
 */
final class MobilePayConfigProvider extends \PensoPay\Payment\Model\Ui\ConfigProvider
{
    const CODE = 'pensopay_mobilepay';
    protected string $_code = self::CODE;
}
