<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class Cancel extends Generic
{
    public function getButtonData()
    {
        if (!$this->hasPayment() || !$this->getPayment()->canCancel()) {
            return [];
        }

        return [
            'label' => __('Cancel'),
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s');",
                __('Are you sure you cancel this payment?'),
                $this->getUrl('*/*/cancelPayment', ['id' => $this->_payment->getId()])
            ),
            'class' => 'save primary',
            'sort_order' => 10
        ];
    }
}
