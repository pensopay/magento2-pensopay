<?php

namespace PensoPay\Payment\Helper;

use Magento\Backend\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use PensoPay\Payment\Model\Payment as PensoPayPayment;

/**
 * Checkout workflow helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PAYMENT_METHODS_XML_PATH = 'payment/pensopay/payment_methods';
    const SPECIFIED_PAYMENT_METHOD_XML_PATH = 'payment/pensopay/payment_method_specified';

    const PRIVATE_KEY_XML_PATH           = 'payment/pensopay/private_key';
    const TESTMODE_XML_PATH              = 'payment/pensopay/testmode';
    const TRANSACTION_FEE_LABEL_XML_PATH = 'payment/pensopay/transaction_fee_label';
    const TRANSACTION_FEE_SKU            = 'transaction_fee';

    const PUBLIC_KEY_XML_PATH      = 'payment/pensopay/public_key';
    const BRANDING_ID_XML_PATH = 'payment/pensopay/branding_id';
    const TRANSACTION_FEE_XML_PATH = 'payment/pensopay/transaction_fee';
    const AUTOCAPTURE_XML_PATH = 'payment/pensopay/autocapture';
    const TEXT_ON_STATEMENT_XML_PATH = 'payment/pensopay/text_on_statement';
    const NEW_ORDER_STATUS_BEFORE_XML_PATH = 'payment/pensopay/new_order_status_before_payment';
    const NEW_ORDER_STATUS_XML_PATH = 'payment/pensopay/new_order_status';

    /** @var Session $_backendSession */
    protected $_backendSession;

    /** @var TransportBuilder $_transportBuilder */
    protected $_transportBuilder;

    /** @var OrderRepository $_orderRepository */
    protected $_orderRepository;

    /** @var SearchCriteriaBuilder $_searchCriteriaBuilder */
    protected $_searchCriteriaBuilder;

    public function __construct(
        Context $context,
        Session $backendSession,
        TransportBuilder $transportBuilder,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->_backendSession = $backendSession;
        $this->_transportBuilder = $transportBuilder;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getStoreIdForOrderIncrement($orderIncrement)
    {
        $searchCriteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $orderIncrement, 'eq')->create();
        $orderList = $this->_orderRepository->getList($searchCriteria)->getItems();
        if (count($orderList)) {
            /** @var Order $order */
            foreach ($orderList as $order) {
                return $order->getStoreId();
            }
        }
        return null;
    }

    public function getBackendSession()
    {
        return $this->_backendSession;
    }

    public function getStatusColorCode($value)
    {
        switch ($value) {
            case PensoPayPayment::STATUS_WAITING_APPROVAL:
            case PensoPayPayment::STATUS_3D_SECURE_REQUIRED:
                $colorClass = 'grid-severity-minor';
                break;
            case PensoPayPayment::STATUS_ABORTED:
            case PensoPayPayment::STATUS_GATEWAY_ERROR:
            case PensoPayPayment::COMMUNICATIONS_ERROR_ACQUIRER:
            case PensoPayPayment::STATUS_AUTHORIZATION_EXPIRED:
            case PensoPayPayment::STATUS_REJECTED_BY_ACQUIRER:
            case PensoPayPayment::STATUS_REQUEST_DATA_ERROR:
                $colorClass = 'grid-severity-major';
                break;
            case PensoPayPayment::STATUS_APPROVED:
            default:
                $colorClass = 'grid-severity-notice';
        }
        return $colorClass;
    }

    /**
     * @param $email
     * @param $name
     * @param $amount
     * @param $currency
     * @param $link
     * @return bool
     * @throws \Exception
     */
    public function sendEmail($email, $name, $amount, $currency, $link)
    {

        $vars = [
            'currency' => $currency,
            'amount' => $amount,
            'link' => $link
        ];

        $senderName = $this->scopeConfig->getValue('trans_email/ident_sales/name');
        $senderEmail = $this->scopeConfig->getValue('trans_email/ident_sales/email');

        $postObject = new DataObject();
        $postObject->setData($vars);

        $sender = [
            'name' => $senderName,
            'email' => $senderEmail,
        ];

        $transport = $this->_transportBuilder->setTemplateIdentifier('pensopay_paymentlink_email')
            ->setTemplateOptions([
                'area' => Area::AREA_FRONTEND,
                'store' => Store::DEFAULT_STORE_ID
            ])
            ->setTemplateVars($vars)
            ->setFrom($sender)
            ->addTo($email, $name)
            ->setReplyTo($senderEmail)
            ->getTransport();
        $transport->sendMessage();
        return true;
    }

    public function setNewOrderStatus(OrderInterface $order, $beforePayment = false)
    {
        if ($beforePayment) {
            $status = $this->getNewOrderStatusBeforePayment();
        } else {
            $status = $this->getNewOrderStatus();
        }

        $states = [
            Order::STATE_NEW,
            Order::STATE_PROCESSING,
            Order::STATE_COMPLETE,
            Order::STATE_CLOSED,
            Order::STATE_CANCELED,
            Order::STATE_HOLDED
        ];

        $state = false;
        foreach ($states as $_state) {
            $stateStatuses = $order->getConfig()->getStateStatuses($_state);
            if (array_key_exists($status, $stateStatuses)) {
                $state = $_state;
                break;
            }
        }

        if ($state) {
            $order->setState($state)
                ->setStatus($status);
        }
    }

    /**
     * Get payment methods
     *
     * @return string
     */
    public function getPaymentMethods()
    {
        $payment_methods = $this->scopeConfig->getValue(self::PAYMENT_METHODS_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        /**
         * Get specified payment methods
         */
        if ($payment_methods === 'specified') {
            $payment_methods = $this->scopeConfig->getValue(self::SPECIFIED_PAYMENT_METHOD_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $payment_methods;
    }

    public function getPrivateKey()
    {
        return $this->scopeConfig->getValue(self::PRIVATE_KEY_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getIsTestmode()
    {
        return $this->scopeConfig->isSetFlag(self::TESTMODE_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getTransactionFeeLabel()
    {
        return $this->scopeConfig->getValue(self::TRANSACTION_FEE_LABEL_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getIsTransactionFee()
    {
        return $this->scopeConfig->isSetFlag(self::TRANSACTION_FEE_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getPublicKey($storeId = null)
    {
        return $this->scopeConfig->getValue(self::PUBLIC_KEY_XML_PATH, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getBrandingId()
    {
        return $this->scopeConfig->getValue(self::BRANDING_ID_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getIsAutocapture()
    {
        return $this->scopeConfig->isSetFlag(self::AUTOCAPTURE_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getTextOnStatement()
    {
        return $this->scopeConfig->getValue(self::TEXT_ON_STATEMENT_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getNewOrderStatus()
    {
        return $this->scopeConfig->getValue(self::NEW_ORDER_STATUS_XML_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getNewOrderStatusBeforePayment()
    {
        return $this->scopeConfig->getValue(self::NEW_ORDER_STATUS_BEFORE_XML_PATH, ScopeInterface::SCOPE_STORE);
    }
}
