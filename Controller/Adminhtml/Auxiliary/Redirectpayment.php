<?php

namespace PensoPay\Payment\Controller\Adminhtml\Auxiliary;

use Magento\Backend\App\Action;

class Redirectpayment extends Action
{
    public function execute()
    {
        $method = $this->getRequest()->getParam('type');
        $this->getResponse()->setRedirect($this->_url->getUrl('adminhtml/system_config/edit/section/payment') . '#' . $method);
        return $this->getResponse();
    }

    protected function _isAllowed()
    {
        return true; //no reason to enforce this here
    }
}
