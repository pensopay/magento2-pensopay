<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class SaveAndSend extends Generic
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        if ($this->hasPayment()) {
            return [];
        }

        return [
            'label' => __('Send Payment Link'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'saveAndSend']],
                'form-role' => 'saveAndSend',
            ]
        ];
    }
}
