<?php

namespace PensoPay\Payment\Controller\Adminhtml\Virtualterminal;

class CancelPayment extends Generic
{
    public function execute()
    {
        return $this->_genericPaymentCallback('cancel');
    }
}
