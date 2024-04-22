<?php
namespace PensoPay\Payment\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use PensoPay\Payment\Model\Config\Source\CheckoutMethods;

/**
 * Checkout workflow helper
 */
class Checkout extends AbstractHelper
{
    const XML_PATH_CHECKOUT_METHOD = 'payment/pensopay/checkout_method';
    const XML_PATH_CHECKOUT_CARDLOGOS = 'payment/pensopay/cardlogos';
    const XML_PATH_CHECKOUT_CARDLOGOS_SIZE = 'payment/pensopay/cardlogos_size';

    const IS_VIRTUAL_TERMINAL = 'is_virtualterminal';

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /** @var \Magento\Payment\Helper\Data $_paymentHelper */
    protected $_paymentHelper;

    /** @var Config $_paymentConfig */
    protected $_paymentConfig;

    /**
     * Checkout constructor.
     * @param Context $context
     * @param CheckoutSession $session
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param Config $paymentConfig
     */
    public function __construct(
        Context $context,
        CheckoutSession $session,
        \Magento\Payment\Helper\Data $paymentHelper,
        Config $paymentConfig
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $session;
        $this->_paymentHelper = $paymentHelper;
        $this->_paymentConfig = $paymentConfig;
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @param string $comment Comment appended to order history
     * @return bool True if order cancelled, false otherwise
     * @throws LocalizedException
     */
    public function cancelCurrentOrder($comment)
    {
        /** @var Order $order */
        $order = $this->_checkoutSession->getLastRealOrder();
        if ($order->getId() && ! $order->isCanceled()) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    /**
     * @return CheckoutSession
     */
    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Restores quote
     *
     * @return bool
     */
    public function restoreQuote()
    {
        return $this->_checkoutSession->restoreQuote();
    }

    public function getPensoPayLogos()
    {
        $logos = $this->scopeConfig->getValue(self::XML_PATH_CHECKOUT_CARDLOGOS, ScopeInterface::SCOPE_STORE);
        if (!empty($logos)) {
            return explode(',', $logos);
        }
        return [];
    }

    public function getLogoSize()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CHECKOUT_CARDLOGOS_SIZE, ScopeInterface::SCOPE_STORE);
    }

    public function getSecondaryPaymentLogos()
    {
        $methods = [];
        foreach ($this->scopeConfig->getValue('payment', ScopeInterface::SCOPE_STORE, null) as $code => $data) {
            if (isset($data['active'], $data['model'], $data['cardlogo_enable']) && (bool)$data['active'] && (bool)$data['cardlogo_enable']) {
                $size = $this->scopeConfig->getValue("payment/{$code}/cardlogo_size", ScopeInterface::SCOPE_STORE);
                $arr = explode('pensopay_', $code);
                $methods[array_pop($arr)] = $size;
            }
        }
        return $methods;
    }
}
