<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;

class EmbeddedCancel extends Action
{
    /** @var JsonFactory $_jsonFactory */
    protected $_jsonFactory;

    /** @var \PensoPay\Payment\Helper\Checkout $_checkoutHelper */
    protected $_checkoutHelper;

    /** @var \PensoPay\Payment\Model\PaymentFactory $_paymentFactory */
    protected $_paymentFactory;
    /**
     * Class constructor
     * @param Context $context
     * @param OrderInterface $orderRepository
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        \PensoPay\Payment\Helper\Checkout $checkoutHelper,
        \PensoPay\Payment\Model\PaymentFactory $paymentFactory
    ) {
        $this->_jsonFactory = $jsonFactory;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_paymentFactory = $paymentFactory;
        parent::__construct($context);
    }

    /**
     * Handle cancellation of payment from iframe
     */
    public function execute()
    {
        $session = $this->_checkoutHelper->getCheckoutSession();

        /** @var \PensoPay\Payment\Model\Payment $payment */
        $payment = $this->_paymentFactory->create();
        $payment->load($session->getLastRealOrder()->getIncrementId(), 'order_id');
        $payment->setState(\PensoPay\Payment\Model\Payment::STATE_REJECTED);
        $payment->save();

        $this->_redirect('pensopay/payment/cancelAction');
    }
}
