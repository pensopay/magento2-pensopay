<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class Capture extends Generic
{
    public function getButtonData()
    {
        if (!$this->hasPayment() || !$this->getPayment()->canCapture()) {
            return [];
        }

        return [
            'label' => __('Capture'),
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s');",
                __('Are you sure you capture this payment?'),
                $this->getUrl('*/*/capturePayment', ['id' => $this->_payment->getId()])
            ),
            'class' => 'save primary',
            'sort_order' => 10
        ];
    }
}
