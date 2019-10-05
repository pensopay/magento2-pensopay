<?php

namespace PensoPay\Payment\Controller\Adminhtml\Virtualterminal;

class UpdatePaymentStatus extends Generic
{
    public function execute()
    {
        $paymentModel = $this->_getPayment();
        if ($paymentModel !== null && $paymentModel->getId()) {
            try {
                $paymentModel->updatePaymentRemote();
                $paymentModel->save();
                $this->messageManager->addSuccessMessage(__('Payment updated successfully.'));
                return $this->_redirect('*/*/edit', ['id' => $paymentModel->getId()]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $this->_redirectToTerminal(); //go to terminal to avoid inf loop in extreme cases
            }
        }
        return $this->_redirectToTerminal(__('Payment not found.'));
    }
}
