<?php

namespace PensoPay\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CheckoutMethods implements ArrayInterface
{
    const METHOD_REDIRECT = 'redirect';
    const METHOD_IFRAME   = 'iframe';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::METHOD_REDIRECT,
                'label' => __('Redirect')
            ],
            [
                'value' => self::METHOD_IFRAME,
                'label' => __('IFrame')
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::METHOD_REDIRECT => __('Redirect'),
            self::METHOD_IFRAME => __('Iframe')
        ];
    }
}
