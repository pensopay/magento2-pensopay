<?php

namespace PensoPay\Payment\Controller\Adminhtml\Virtualterminal;

class UpdateAndPay extends Generic
{
    public function execute()
    {
        if ($this->_updatePaymentLink(false)) {
            $this->_getSession()->setPaymentLinkAutovisit(true);
        }
        return $this->_redirectToTerminal();
    }
}
