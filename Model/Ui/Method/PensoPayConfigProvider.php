<?php
namespace PensoPay\Payment\Model\Ui\Method;

/**
 * Class PensoPayConfigProvider
 */
final class PensoPayConfigProvider extends \PensoPay\Payment\Model\Ui\ConfigProvider
{
    const CODE = 'pensopay';
    protected string $_code = self::CODE;
}
