<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class PollPayment extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /** @var JsonFactory $_jsonFactory */
    protected $_jsonFactory;

    /** @var \PensoPay\Payment\Helper\Checkout $_checkoutHelper */
    protected $_checkoutHelper;

    /** @var \PensoPay\Payment\Model\PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    /** @var \Magento\Framework\Message\ManagerInterface $_messageManager */
    protected $_messageManager;

    /**
     * Class constructor
     * @param Context $context
     * @param LoggerInterface $logger
     * @param OrderInterface $orderRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        OrderInterface $orderRepository,
        JsonFactory $jsonFactory,
        \PensoPay\Payment\Helper\Checkout $checkoutHelper,
        \PensoPay\Payment\Model\PaymentFactory $paymentFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->_jsonFactory = $jsonFactory;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_paymentFactory = $paymentFactory;
        $this->_messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * Redirect to to PensoPay
     *
     * @return string
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultPage */
        $resultPage = $this->_jsonFactory->create();
        $resultPage->setStatusHeader(200);

        $checkoutSession = $this->_checkoutHelper->getCheckoutSession();
        $order = $checkoutSession->getLastRealOrder();
        if ($order) {
            /** @var \PensoPay\Payment\Model\Payment $payment */
            $payment = $this->_paymentFactory->create();
            $payment->load($order->getIncrementId(), 'order_id');
            if ($payment->getData('id')) {
                try {
                    if ($payment->getState() === \PensoPay\Payment\Model\Payment::STATE_REJECTED) { //Check if cancelled from iframe first
                        $resultPage->setData(
                            [
                                'repeat' => 0,
                                'error' => 1,
                                'success' => 0,
                                'redirect' => $this->_url->getUrl('pensopay/payment/cancelAction')
                            ]
                        );
                        return $resultPage;
                    }

                    $payment->updatePaymentRemote();

                    if (in_array($payment->getLastType(), [
                        \PensoPay\Payment\Model\Payment::OPERATION_AUTHORIZE,
                        \PensoPay\Payment\Model\Payment::OPERATION_CAPTURE], true)
                    ) {
                        if ($payment->getLastCode() == \PensoPay\Payment\Model\Payment::STATUS_APPROVED) {
                            $resultPage->setData(
                                [
                                    'repeat' => 0,
                                    'error' => 0,
                                    'success' => 1,
                                    'redirect' => $this->_url->getUrl('pensopay/payment/returnAction')
                                ]
                            );
                        } else {
                            $this->_messageManager->addErrorMessage(
                                __('There was a problem with the payment. Please try again.')
                            );
                            $resultPage->setData(
                                [
                                    'repeat' => 0,
                                    'error' => 1,
                                    'success' => 0,
                                    'redirect' => $this->_url->getUrl('pensopay/payment/cancelAction')
                                ]
                            );
                        }
                        return $resultPage;
                    }

                    $resultPage->setData(
                        [
                            'repeat' => 1,
                            'error' => 0,
                            'success' => 0,
                            'redirect' => ''
                        ]
                    );
                } catch (\Exception $e) {
                }
                return $resultPage;
            }
            $resultPage->setData(
                [
                    'repeat' => 0,
                    'error' => 1,
                    'success' => 0,
                    'redirect' => $this->_url->getUrl('/')
                ]
            );
        }

        $resultPage->setData([
            'repeat' => 1,
            'error' => 0,
            'success' => 0,
            'redirect' => ''
        ]);
        return $resultPage;
        //In case someone is hitting this on purpose or expired sessions
        return $this->_redirect('/');
    }
}
