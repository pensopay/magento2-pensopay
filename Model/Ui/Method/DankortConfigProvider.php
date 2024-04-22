<?php
namespace PensoPay\Payment\Model\Ui\Method;

/**
 * Class DankortConfigProvider
 */
final class DankortConfigProvider extends \PensoPay\Payment\Model\Ui\ConfigProvider
{
    const CODE = 'pensopay_dankort';
    protected string $_code = self::CODE;
}
