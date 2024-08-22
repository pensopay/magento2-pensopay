<?php

namespace PensoPay\Payment\Model\Adapter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use PensoPay\Payment\Helper\Checkout as PensoPayHelperCheckout;
use PensoPay\Payment\Helper\Data as PensoPayHelperData;
use PensoPay\Payment\Model\Payment;
use PensoPay\Payment\Model\PaymentFactory;
use PensoPay\Payment\Model\Ui\Method\AnydayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\MobilePayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\ViabillConfigProvider;
use PensoPay\Payment\Model\Ui\Method\DankortConfigProvider;
use PensoPay\Payment\Model\Ui\Method\KlarnaPaymentsConfigProvider;
use PensoPay\Payment\Model\Ui\Method\PayPalConfigProvider;
use PensoPay\Payment\Model\Ui\Method\VippsConfigProvider;
use Magento\Framework\Event\ManagerInterface as EventManager;

use Psr\Log\LoggerInterface;
use QuickPay\QuickPay;
use Symfony\Component\Intl\Countries;

/**
 * Class PensoPayAdapter
 */
class PensoPayAdapter
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var PensoPayHelperData
     */
    protected $helper;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /** @var mixed $_apiKey */
    protected $_apiKey;

    /** @var QuickPay $_client */
    protected $_client;

    /** @var PensoPayHelperCheckout $_checkoutHelper */
    protected $_checkoutHelper;

    /** @var PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    /** @var StoreManagerInterface $_storeManager */
    protected $_storeManager;

    /** @var \Magento\Store\Model\Store $_frontStore */
    protected $_frontStore;

    /** @var PensoPayHelperData $_pensoHelper */
    protected $_pensoHelper;

    /** @var EncryptorInterface $_encryptor */
    protected $_encryptor;

    /** @var EventManager $_eventManager */
    protected $_eventManager;


    public function __construct(
        LoggerInterface $logger,
        UrlInterface $url,
        PensoPayHelperData $helper,
        ScopeConfigInterface $scopeConfig,
        ResolverInterface $resolver,
        OrderRepositoryInterface $orderRepository,
        PensoPayHelperCheckout $checkoutHelper,
        PaymentFactory $paymentFactory,
        StoreManagerInterface $storeManager,
        PensoPayHelperData $pensoHelper,
        EncryptorInterface $encryptor,
        EventManager $eventManager
    ) {
        $this->logger = $logger;
        $this->url = $url;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->resolver = $resolver;
        $this->orderRepository = $orderRepository;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_paymentFactory = $paymentFactory;
        $this->_storeManager = $storeManager;
        $this->_pensoHelper = $pensoHelper;
        $this->_encryptor = $encryptor;
        $this->_eventManager = $eventManager;

        $this->_apiKey = $this->helper->getPublicKey();
        $this->_client = new QuickPay(":{$this->_apiKey}");
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|\Magento\Store\Model\Store
     */
    protected function getFrontStore()
    {
        if (!$this->_frontStore) {
            //This feels a little more reliable than get default store due to the ?: null
            //Intentionally structured as a loop to avoid having to check for empty results etc
            foreach ($this->_storeManager->getStores() as $store) {
                $this->_frontStore = $store;
                break;
            }
        }
        return $this->_frontStore;
    }

    protected function getFrontUrl($path = '', $params = [])
    {
        if ($this->url instanceof \Magento\Backend\Model\UrlInterface) {
            $store = $this->getFrontStore();
            return $store->getUrl($path, $params);
        }
        return $this->url->getUrl($path, $params);
    }

    /**
     * Authorize payment and create payment link
     *
     * @param array $attributes
     * @param bool $autoSave
     * @return array|bool
     */
    public function authorizeAndCreatePaymentLink(array $attributes, $autoSave = true)
    {
        try {
            $isVirtualTerminal = isset($attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL]) && $attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL];
            $form = $this->_setupRequest($attributes);

            $payments = $this->_client->request->post('/payments', $form);
            $paymentArray = $payments->asArray();
            $paymentId = $paymentArray['id'];

            $paymentArray['link'] = $this->createPaymentLink($attributes, $paymentId);

            if ($isVirtualTerminal) {
                $this->_setExtraVirtualTerminalData($attributes, $paymentArray);
            }

            if ($autoSave) {
                $this->_autoSave($paymentArray);
            }

            return $paymentArray;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return true;
    }

    protected function _setExtraVirtualTerminalData($attributes, &$paymentArray)
    {
        $paymentArray[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL] = true;
        $paymentArray['customer_name'] = $attributes['CUSTOMER_NAME'];
        $paymentArray['customer_email'] = $attributes['CUSTOMER_EMAIL'];
        $paymentArray['customer_street'] = $attributes['CUSTOMER_STREET'];
        $paymentArray['customer_zipcode'] = $attributes['CUSTOMER_ZIPCODE'];
        $paymentArray['customer_city'] = $attributes['CUSTOMER_CITY'];
        return $paymentArray;
    }

    protected function _setupRequest(&$attributes)
    {
        $form = [
            'order_id' => $attributes['INCREMENT_ID'],
            'currency' => $attributes['CURRENCY'],
        ];

        $storeId = $this->_pensoHelper->getStoreIdForOrderIncrement($attributes['INCREMENT_ID']);
        if (is_numeric($storeId)) {
            $this->setTransactionStore($storeId);
        }

        $order = false;
        $isVirtualTerminal = isset($attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL]) && $attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL];
        if (!$isVirtualTerminal) {
            $shippingAddress = $attributes['SHIPPING_ADDRESS'];
            $form['shipping_address'] = [];
            $form['shipping_address']['name'] = $shippingAddress->getFirstName() . ' ' . $shippingAddress->getLastName();
            $form['shipping_address']['street'] = $shippingAddress->getStreetLine1();
            $form['shipping_address']['city'] = $shippingAddress->getCity();
            $form['shipping_address']['zip_code'] = $shippingAddress->getPostcode();
            $form['shipping_address']['region'] = $shippingAddress->getRegionCode();
            $form['shipping_address']['country_code'] = Countries::getAlpha3Code($shippingAddress->getCountryId());
            $form['shipping_address']['phone_number'] = $shippingAddress->getTelephone();
            $form['shipping_address']['email'] = $shippingAddress->getEmail();

            $order = $this->orderRepository->get($attributes['ORDER_ID']);

            $billingAddress = $attributes['BILLING_ADDRESS'];
            $form['invoice_address'] = [];
            $form['invoice_address']['name'] = $billingAddress->getFirstName() . ' ' . $billingAddress->getLastName();
            $form['invoice_address']['street'] = $billingAddress->getStreetLine1();
            $form['invoice_address']['city'] = $billingAddress->getCity();
            $form['invoice_address']['zip_code'] = $billingAddress->getPostcode();
            $form['invoice_address']['region'] = $billingAddress->getRegionCode();
            $form['invoice_address']['country_code'] = Countries::getAlpha3Code($billingAddress->getCountryId());
            $form['invoice_address']['phone_number'] = $billingAddress->getTelephone();
            $form['invoice_address']['email'] = $billingAddress->getEmail();

            $attributes['PAYMENT_METHOD'] = $order->getPayment()->getMethod();

//            if ($attributes['PAYMENT_METHOD'] !== KlarnaPaymentsConfigProvider::CODE) {
                $form['shipping'] = [
                    'amount' => $order->getBaseShippingInclTax() * 100
                ];
//            }

            //Build basket array
            $items = $attributes['ITEMS'];
            $form['basket'] = [];
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($items as $item) {
                if ((!$item->getPrice() && $item->getParentItemId()) || ($item->getParentItem() && $item->getParentItem()->getProductType() === 'bundle')) {
                    continue; //Simples of configurables that carry no prices aren't wanted, as well as bundle options because they are counted as additions to the cart
                }
                $form['basket'][] = [
                    'qty' => (int)$item->getQtyOrdered(),
                    'item_no' => $item->getSku(),
                    'item_name' => $item->getName(),
                    'item_price' => (float)(round(($item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount()) / $item->getQtyOrdered(), 2) * 100),
                    'vat_rate' => $item->getTaxPercent() / 100
                ];
            }

//            if ($attributes['PAYMENT_METHOD'] === KlarnaPaymentsConfigProvider::CODE) {
//                $form['basket'][] = [
//                    'qty' => 1,
//                    'item_no' => 'shipping',
//                    'item_name' => 'Shipping',
//                    'item_price' => (int)($order->getShippingInclTax() * 100),
//                    'vat_rate' => 0,
//                ];
//            }

        } else {
            $form['basket'] = [
                [
                    'qty'        => 1,
                    'item_no'    => 'virtualterminal',
                    'item_name'  => 'Products',
                    'item_price' => $attributes['AMOUNT'],
                    'vat_rate'   => 0.25, //TODO
                ]
            ];
        }

        $dataObject = new \Magento\Framework\DataObject();
        $dataObject->setForm($form);
        $dataObject->setOrder($order);

        $this->_eventManager->dispatch('pensopay_formdata_construct_after', ['data_object' => $dataObject]);

        $form = $dataObject->getForm();

        return $form;
    }

    public function updatePaymentAndPaymentLink($attributes, $autoSave = true)
    {
        try {
            $form = $this->_setupRequest($attributes);

            $isVirtualTerminal = isset($attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL]) && $attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL];
            if ($isVirtualTerminal) {
                $form['id'] = $attributes['ORDER_ID'];
            }

            $payments = $this->_client->request->patch(sprintf('/payments/%s', $form['id']), $form);
            $paymentArray = $payments->asArray();
            $paymentId = $paymentArray['id'];

            $paymentArray['link'] = $this->createPaymentLink($attributes, $paymentId);

            if ($isVirtualTerminal) {
                $this->_setExtraVirtualTerminalData($attributes, $paymentArray);
            }

            if ($autoSave) {
                $paymentArray['payment_id'] = $attributes['payment_id'];
                $this->_autoSave($paymentArray, true);
            }

            return $paymentArray;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        return true;
    }

    protected function _autoSave($payment, $update = false)
    {
        /** @var Payment $paymentObject */
        $paymentObject = $this->_paymentFactory->create();

        if ($update) {
            $paymentId = $payment['payment_id'];
            $paymentObject->load($paymentId);
            if ($paymentObject->getId()) {
                $paymentObject->importFromRemotePayment($payment);
                $paymentObject->setId($paymentId);
            }
        } else {
            $paymentObject->importFromRemotePayment($payment);
        }
        $paymentObject->save();
    }

    public function createPaymentLink($attributes, $paymentId)
    {
        $parameters = [
            'amount' => $attributes['AMOUNT'],
            'callbackurl' => $this->getFrontUrl('pensopay/payment/callback', ['isAjax' => true]), //We add isAjax to counter magento 2.3 CSRF protection
            'customer_email' => $attributes['EMAIL']
        ];
        $isVirtualTerminal = isset($attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL]) && $attributes[PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL];
        if (!$isVirtualTerminal) {
            $parameters['autocapture'] = $this->helper->getIsAutocapture();
            $parameters['language'] = $this->getLanguage();
            $parameters['auto_fee'] = $this->helper->getIsTransactionFee();
        } else {
            $parameters['autocapture'] = $attributes['AUTOCAPTURE'];
            $parameters['language'] = $this->getResolvedLanguage($attributes['LANGUAGE']);
            $parameters['auto_fee'] = $this->helper->getIsTransactionFee();
        }

        if (!$isVirtualTerminal) {
            $parameters['continueurl'] = $this->getFrontUrl('pensopay/payment/returnAction', ['_query' => ['ori' => $this->_encryptor->encrypt($attributes['INCREMENT_ID'])]]);
            $parameters['cancelurl'] = $this->getFrontUrl('pensopay/payment/cancelAction');
        }

        switch ($attributes['PAYMENT_METHOD']) {
            case ViabillConfigProvider::CODE:
                $parameters['payment_methods'] = 'viabill';
                break;
            case AnydayConfigProvider::CODE:
                $parameters['payment_methods'] = 'anyday-split';
                break;
            case MobilePayConfigProvider::CODE:
                $parameters['payment_methods'] = 'mobilepay';
                break;
            case DankortConfigProvider::CODE:
                $parameters['payment_methods'] = 'dankort';
                break;
            case KlarnaPaymentsConfigProvider::CODE:
                $parameters['payment_methods'] = 'klarna-payments';
                break;
            case PayPalConfigProvider::CODE:
                $parameters['payment_methods'] = 'paypal';
                break;
            case VippsConfigProvider::CODE:
                $parameters['payment_methods'] = 'vipps,vippspsp';
                break;
            default: //Covers default payment method - pensopay
                $parameters['payment_methods'] = $this->helper->getPaymentMethods();
                break;
        }

        if ($textOnStatement = $this->helper->getTextOnStatement()) {
            $parameters['text_on_statement'] = $textOnStatement;
        }

        if ($brandingId = $this->helper->getBrandingId()) {
            $parameters['branding_id'] = $brandingId;
        }

        //Create payment link and return payment id
        $paymentLink = $this->_client->request->put(sprintf('/payments/%s/link', $paymentId), $parameters)->asArray();
        return $paymentLink['url'];
    }

    /**
     * Capture payment
     *
     * @param array $attributes
     * @return array|bool
     */
    public function capture(array $attributes)
    {
        try {
            $this->setTransactionStore($attributes['STORE_ID']);
            $form = [
                'id'     => $attributes['TXN_ID'],
                'amount' => $attributes['AMOUNT'],
            ];

            $id = $attributes['TXN_ID'];

            $payments = $this->_client->request->post("/payments/{$id}/capture?synchronized", $form);
            return $payments->asArray();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return true;
    }

    /**
     * Get Payment data from remote.
     *
     * @param $paymentId
     * @return array
     * @throws \Exception
     */
    public function getPayment($paymentId)
    {
        $this->logger->debug("Updating payment state for {$paymentId}");

        try {
            $payments = $this->_client->request->get("/payments/{$paymentId}");
            return $payments->asArray();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw $e;
        }
    }

    public function setTransactionStore($storeId)
    {
        $apiKey = $this->helper->getPublicKey($storeId);
        if (empty($apiKey)) {
            $apiKey = $this->helper->getPublicKey(null);
        }
        $this->_apiKey = $apiKey;
        $this->_client = new QuickPay(":{$this->_apiKey}");
    }

    /**
     * Cancel payment
     *
     * @param array $attributes
     * @return array|bool
     */
    public function cancel(array $attributes)
    {
        $this->logger->debug('Cancel payment');
        try {
            $this->setTransactionStore($attributes['STORE_ID']);
            $form = [
                'id' => $attributes['TXN_ID'],
            ];

            $this->logger->debug(var_export($form, true));

            $id = $attributes['TXN_ID'];

            $payments = $this->_client->request->post("/payments/{$id}/cancel?synchronized", $form);
            $paymentArray = $payments->asArray();

            $this->logger->debug(var_export($paymentArray, true));

            /**
             * This is required to prevent a few obscure errors with cancelling payments on the QP Gateway.
             * Since cancellation itself is not vital to normal operation this is okay.
             */
            $paymentArray['accepted'] = true;
            $paymentArray['id'] = '000';

            return $paymentArray;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return true;
    }

    /**
     * Refund payment
     *
     * @param array $attributes
     * @return array|bool
     */
    public function refund(array $attributes)
    {
        $this->logger->debug('Refund payment');
        try {
            $this->setTransactionStore($attributes['STORE_ID']);
            $form = [
                'id' => $attributes['TXN_ID'],
                'amount' => $attributes['AMOUNT'],
            ];

            $this->logger->debug(var_export($form, true));

            $id = $attributes['TXN_ID'];

            $payments = $this->_client->request->post("/payments/{$id}/refund?synchronized", $form);
            $paymentArray = $payments->asArray();

            $this->logger->debug(var_export($paymentArray, true));

            return $paymentArray;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return true;
    }

    /**
     * Get language code from locale
     *
     * @return mixed
     */
    private function getLanguage()
    {
        return $this->getResolvedLanguage($this->resolver->getLocale());
    }

    private function getResolvedLanguage($lang)
    {
        //Map both norwegian locales to no
        $map = [
            'nb' => 'no',
            'nn' => 'no',
        ];

        $language = explode('_', $lang)[0];

        if (isset($map[$language])) {
            return $map[$language];
        }

        return $language;
    }
}
