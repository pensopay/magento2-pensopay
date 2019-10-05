<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Fieldset;

use Magento\Framework\View\Element\Context;

class Status extends \Magento\Framework\View\Element\AbstractBlock
{
    /** @var \PensoPay\Payment\Model\PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    /** @var \PensoPay\Payment\Helper\Data $_pensoPayHelper\ */
    protected $_pensoPayHelper;

    public function __construct(
        Context $context,
        \PensoPay\Payment\Model\PaymentFactory $paymentFactory,
        \PensoPay\Payment\Helper\Data $pensoPayHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_paymentFactory = $paymentFactory;
        $this->_pensoPayHelper = $pensoPayHelper;
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        $paymentId = $this->_request->getParam('id');
        if ($paymentId) {
            /** @var \PensoPay\Payment\Model\Payment $payment */
            $payment = $this->_paymentFactory->create();
            $payment->load($paymentId);
            if ($payment->getId()) {
                $extraClass = $this->_pensoPayHelper->getStatusColorCode($payment->getLastCode());
                return "
                <div class='payment-status {$extraClass}'>
                    {$payment->getDisplayStatus()}
                </div>";
            }
        }
        return parent::_toHtml();
    }
}
