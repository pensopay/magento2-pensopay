<?php

namespace PensoPay\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CheckoutMethods implements ArrayInterface
{
    const METHOD_REDIRECT = 'redirect';

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
            self::METHOD_REDIRECT => __('Redirect')
        ];
    }
}
