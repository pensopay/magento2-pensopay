<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use PensoPay\Payment\Helper\Data;
use PensoPay\Payment\Model\PaymentFactory;
use Psr\Log\LoggerInterface;

class Callback extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderInterface
     */
    protected $order;

    protected QuoteRepository $_quoteRepository;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /** @var Data $_pensoPayHelper */
    protected $_pensoPayHelper;

    protected $_pensoPaymentFactory;

    /**
     * Class constructor
     * @param Context              $context
     * @param LoggerInterface                           $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        OrderInterface $order,
        QuoteRepository $quoteRepository,
        OrderSender $orderSender,
        Data $pensoPayHelper,
        PaymentFactory $paymentFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->order = $order;
        $this->_quoteRepository = $quoteRepository;
        $this->orderSender = $orderSender;
        $this->_pensoPayHelper = $pensoPayHelper;
        $this->_pensoPaymentFactory = $paymentFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    /**
     * Handle callback from PensoPay
     *
     * @return string
     */
    public function execute()
    {
        $body = $this->getRequest()->getContent();

        try {
            $response = json_decode($body);

            //Fetch private key from config and validate checksum
            $key = $this->_pensoPayHelper->getPrivateKey();
            $checksum = hash_hmac('sha256', $body, $key);
            $submittedChecksum = $this->getRequest()->getServer('HTTP_QUICKPAY_CHECKSUM_SHA256');

            if ($checksum === $submittedChecksum) {
                //Make sure that payment is accepted
                if ($response->accepted === true) {
                    /**
                     * Load order by incrementId
                     * @var Order $order
                     */
                    $order = $this->order->loadByIncrementId($response->order_id);

                    if (!$order->getId()) {
                        $this->logger->debug('Failed to load order with id: ' . $response->order_id);
                        return;
                    }

                    //Cancel order if testmode is disabled and this is a test payment
                    $testMode = $this->_pensoPayHelper->getIsTestmode();

                    if (!$testMode && $response->test_mode === true) {
                        $this->logger->debug('Order attempted paid with a test card but testmode is disabled.');
                        if (!$order->isCanceled()) {
                            $order->registerCancellation("Order attempted paid with test card")->save();
                        }
                        return;
                    }

                    //Add card metadata
                    $payment = $order->getPayment();
                    if (isset($response->metadata->type) && $response->metadata->type === 'card') {
                        $payment->setCcType($response->metadata->brand);
                        $payment->setCcLast4('xxxx-' . $response->metadata->last4);
                        $payment->setCcExpMonth($response->metadata->exp_month);
                        $payment->setCcExpYear($response->metadata->exp_year);

                        $payment->setAdditionalInformation('cc_number', 'xxxx-' . $response->metadata->last4);
                        $payment->setAdditionalInformation('exp_month', $response->metadata->exp_month);
                        $payment->setAdditionalInformation('exp_year', $response->metadata->exp_year);
                        $payment->setAdditionalInformation('cc_type', $response->metadata->brand);
                    } else {
                        if (isset($response->metadata->payment_method)) {
                            $payment->setCcType($response->metadata->payment_method);
                            $payment->setAdditionalInformation('cc_type', $response->metadata->payment_method);
                        }
                    }

                    //Add transaction fee if set
                    if ($response->fee > 0) {
                        $fee = $response->fee / 100;
                        $currentFee = $order->getData('card_surcharge');
                        $calculatedFee = $fee;
                        if ($currentFee > 0) {
                            $order->setData('card_surcharge', $fee);
                            $order->setData('base_card_surcharge', $fee);
                            $calculatedFee = -$currentFee + $fee;
                        } else {
                            $order->setData('card_surcharge', $fee);
                            $order->setData('base_card_surcharge', $fee);
                        }

                        $order->setGrandTotal($order->getGrandTotal() + $calculatedFee);
                        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $calculatedFee);

                        $quoteId = $order->getQuoteId();
                        if ($quoteId) {
                            /**
                             * Not business critical, don't want to stop
                             * Basically adds support for most order editors.
                             */
                            try {
                                $quote = $this->_quoteRepository->get($quoteId);
                                if ($quote->getId()) {
                                    $quote->setData('card_surcharge', $fee);
                                    $quote->setData('base_card_surcharge', $fee);
                                    $quote->setGrandTotal($quote->getGrandTotal() + $calculatedFee);
                                    $quote->setBaseGrandTotal($quote->getBaseGrandTotal() + $calculatedFee);
                                    $this->_quoteRepository->save($quote);
                                }
                            } catch (\Exception $e) {}
                        }
                    }

                    $pensoPayment = $this->_pensoPaymentFactory->create();
                    $pensoPayment->load($order->getIncrementId(), 'order_id');
                    $pensoPayment->importFromRemotePayment(json_decode($body, true));
                    $pensoPayment->save();

                    $this->_pensoPayHelper->setNewOrderStatus($order);
                    $order->save();

                    //Send order email
                    if (!$order->getEmailSent()) {
                        $this->sendOrderConfirmation($order);
                    }
                }
            } else {
                $this->logger->debug('Checksum mismatch');
                return;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Send order confirmation email
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function sendOrderConfirmation($order)
    {
        try {
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(__('Order confirmation email sent to customer'))
                ->setIsCustomerNotified(true)
                ->save();
        } catch (\Exception $e) {
            $order->addStatusHistoryComment(__('Failed to send order confirmation email: %s', $e->getMessage()))
                ->setIsCustomerNotified(false)
                ->save();
        }
    }
}
