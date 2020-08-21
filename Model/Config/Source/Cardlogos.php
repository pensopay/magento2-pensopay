<?php

namespace PensoPay\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Cardlogos implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'forbrugsforeningen',
                'label' => __('Forbrugsforeningen')
            ],
            [
                'value' => 'visa',
                'label' => __('VISA')
            ],
            [
                'value' => 'dankort',
                'label' => __('Dankort')
            ],
            [
                'value' => 'visaelectron',
                'label' => __('VISA Electron')
            ],
            [
                'value' => 'mastercard',
                'label' => __('MasterCard')
            ],
            [
                'value' => 'maestro',
                'label' => __('Maestro')
            ],
            [
                'value' => 'jcb',
                'label' => __('JCB')
            ],
            [
                'value' => 'diners',
                'label' => __('Diners Club')
            ],
            [
                'value' => 'amex',
                'label' => __('AMEX')
            ],
            [
                'value' => 'sofort',
                'label' => __('Sofort')
            ],
            [
                'value' => 'viabill',
                'label' => __('ViaBill')
            ],
            [
                'value' => 'mobilepay',
                'label' => __('MobilePay')
            ],
            [
                'value' => 'applepay',
                'label' => __('ApplePay')
            ]
        ];
    }
}
