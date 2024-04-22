<?php
namespace PensoPay\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{

    protected string $_code;

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                $this->_code => [
                    'redirectUrl' => 'pensopay/payment/redirect',
                ]
            ]
        ];
    }
}
