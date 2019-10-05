<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class Reset extends Generic
{
    public function getButtonData()
    {
        return [
            'label' => __('Reset'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/*')),
            'class' => 'reset',
            'sort_order' => 20
        ];
    }
}
