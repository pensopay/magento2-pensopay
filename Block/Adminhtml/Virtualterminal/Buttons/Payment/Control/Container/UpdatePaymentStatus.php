<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class UpdatePaymentStatus extends Generic
{
    public function getButtonData()
    {
        if (!$this->hasPayment()) {
            return [];
        }

        return [
            'label' => __('Get Payment Status'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/updatePaymentStatus', ['id' => $this->_payment->getId()])),
            'class' => 'save primary',
            'sort_order' => 10
        ];
    }
}
