<?php

namespace PensoPay\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CheckoutMethods implements ArrayInterface
{
    const METHOD_REDIRECT = 'redirect';
    const METHOD_IFRAME   = 'iframe';
    const METHOD_EMBEDDED = 'embedded';

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
//            [
//                'value' => self::METHOD_EMBEDDED,
//                'label' => __('Embedded')
//            ]
//            [
//                'value' => self::METHOD_IFRAME,
//                'label' => __('IFrame')
//            ]
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
//            self::METHOD_EMBEDDED => __('Embedded'),
//            self::METHOD_IFRAME => __('Iframe')
        ];
    }
}
