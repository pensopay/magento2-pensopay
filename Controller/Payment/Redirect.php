<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use PensoPay\Payment\Gateway\Response\PaymentLinkHandler;
use PensoPay\Payment\Helper\Checkout as PensoPayCheckoutHelper;
use Psr\Log\LoggerInterface;

class Redirect extends \Magento\Framework\App\Action\Action
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /** @var PensoPayCheckoutHelper $_pensopayCheckoutHelper */
    protected $_pensopayCheckoutHelper;

    /** @var StoreManagerInterface $_storeManager3 */
    protected $_storeManager;

    /**
     * Class constructor
     * @param Context $context
     * @param LoggerInterface $logger
     * @param PensoPayCheckoutHelper $pensopayCheckoutHelper
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        PensoPayCheckoutHelper $pensopayCheckoutHelper
    ) {
        $this->_logger = $logger;
        $this->_pensopayCheckoutHelper = $pensopayCheckoutHelper;
        parent::__construct($context);
    }

    /**
     * Redirect to to PensoPay
     *
     * @return string
     */
    public function execute()
    {
        try {
            $order = $this->_pensopayCheckoutHelper->getCheckoutSession()->getLastRealOrder();
            $paymentLink = $order->getPayment()->getAdditionalInformation(PaymentLinkHandler::PAYMENT_LINK);

//            $isIframe = $this->_pensopayCheckoutHelper->isCheckoutIframe();
            $isIframe = false; //Deprecated
            $isEmbedded = $this->_pensopayCheckoutHelper->isCheckoutEmbedded();

            if ($isEmbedded) {
                $paymentMethod = $order->getPayment()->getMethod();
                if (in_array($paymentMethod, ['pensopay_dankort', 'pensopay_klarna', 'pensopay_mobilepay'],
                    true)) { //These do not support embedded
                    $isEmbedded = false;
                }
            }

            if ($isEmbedded) {
                $paymentData = [
                    'payment_link' => $paymentLink,
                    'total' => $order->getGrandTotal(),
                    'currency' => $order->getOrderCurrencyCode(),
                    'redirecturl' => $this->_url->getUrl('pensopay/payment/returnAction'),
                    'cancelurl' => $this->_url->getUrl('pensopay/payment/embeddedCancel')
                ];

                $this->_pensopayCheckoutHelper->getCheckoutSession()->setPaymentData(serialize($paymentData));
                return $this->_redirect('pensopay/payment/embedded');
            }

            if ($isIframe) {
                $this->_pensopayCheckoutHelper->getCheckoutSession()->setPaymentWindowUrl($paymentLink);
                return $this->_redirect('pensopay/payment/iframe');
            }

            return $this->_redirect($paymentLink);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong, please try again later'));
            $this->_logger->critical($e);
            $this->_getCheckout()->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}
