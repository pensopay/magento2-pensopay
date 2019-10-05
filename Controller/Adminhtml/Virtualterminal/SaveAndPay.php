<?php

namespace PensoPay\Payment\Controller\Adminhtml\Virtualterminal;

class SaveAndPay extends Generic
{
    public function execute()
    {
        if ($this->_createPaymentLink(false)) {
            $this->_getSession()->setPaymentLinkAutovisit(true);
        }
        return $this->_redirectToTerminal();
    }
}
