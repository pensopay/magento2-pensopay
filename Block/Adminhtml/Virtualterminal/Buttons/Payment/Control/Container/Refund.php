<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class Refund extends Generic
{
    public function getButtonData()
    {
        if (!$this->hasPayment() || !$this->getPayment()->canRefund()) {
            return [];
        }

        return [
            'label' => __('Refund'),
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s');",
                __('Are you sure you refund this payment?'),
                $this->getUrl('*/*/refundPayment', ['id' => $this->_payment->getId()])
            ),
            'class' => 'save primary',
            'sort_order' => 10
        ];
    }
}
