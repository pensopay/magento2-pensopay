<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

class Back extends Generic
{
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/')),
            'class' => 'back',
            'sort_order' => 10
        ];
    }
}
