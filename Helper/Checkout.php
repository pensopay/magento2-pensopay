<?php
namespace PensoPay\Payment\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use PensoPay\Payment\Model\Config\Source\CheckoutMethods;

/**
 * Checkout workflow helper
 */
class Checkout extends AbstractHelper
{
    const XML_PATH_CHECKOUT_METHOD = 'payment/pensopay/checkout_method';

    const IS_VIRTUAL_TERMINAL = 'is_virtualterminal';

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    public function __construct(
        Context $context,
        CheckoutSession $session
    ) {
        parent::__construct($context);

        $this->_checkoutSession = $session;
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
     * @return bool
     */
    public function isCheckoutIframe()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CHECKOUT_METHOD,
            ScopeInterface::SCOPE_STORE
        ) === CheckoutMethods::METHOD_IFRAME;
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
}
