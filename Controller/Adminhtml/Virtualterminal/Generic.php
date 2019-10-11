<?php

namespace PensoPay\Payment\Controller\Adminhtml\Virtualterminal;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use PensoPay\Payment\Helper\Checkout as PensoPayHelperCheckout;
use PensoPay\Payment\Helper\Data as PensoPayHelper;
use PensoPay\Payment\Model\Adapter\PensoPayAdapter;
use PensoPay\Payment\Model\Payment;
use PensoPay\Payment\Model\PaymentFactory;
use PensoPay\Payment\Model\ResourceModel\Payment\Collection;
use PensoPay\Payment\Model\ResourceModel\Payment\CollectionFactory;

class Generic extends Action
{
    /** @var Context $_context */
    protected $_context;

    /** @var PageFactory $_resultPageFactory */
    protected $_resultPageFactory;

    /** @var PensoPayAdapter $_payAdapter */
    protected $_payAdapter;

    /** @var PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    /** @var Payment $_payment */
    protected $_payment;

    /** @var CollectionFactory $_paymentCollectionFactory */
    protected $_paymentCollectionFactory;

    /** @var PensoPayHelper $_pensoPayHelper */
    protected $_pensoPayHelper;

    /** @var bool $_redirect */
    protected $_redirect = true;

    /**
     * Stores constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param PensoPayAdapter $payAdapter
     * @param PaymentFactory $paymentFactory
     * @param PensoPayHelper $pensoPayHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        PensoPayAdapter $payAdapter,
        PaymentFactory $paymentFactory,
        CollectionFactory $paymentCollectionFactory,
        PensoPayHelper $pensoPayHelper
    ) {
        parent::__construct($context);
        $this->_context = $context;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_payAdapter = $payAdapter;
        $this->_paymentFactory = $paymentFactory;
        $this->_paymentCollectionFactory = $paymentCollectionFactory;
        $this->_pensoPayHelper = $pensoPayHelper;
    }

    protected function _redirectToTerminal($error = '')
    {
        if (!empty($error)) {
            $this->getMessageManager()->addErrorMessage($error);
        }
        return $this->_redirect('pensopay/virtualterminal');
    }

    protected function _getPayment()
    {
        if (!$this->_payment) {
            /** @var RequestInterface $request */
            $request = $this->getRequest();

            $incId = $request->getParam('id');

            /** @var Payment $paymentModel */
            $paymentModel = $this->_paymentFactory->create();
            if (!empty($incId)) { //Existing payment
                $paymentModel->load($incId);
                if (!$paymentModel->getId()) {
                    $this->_redirectToTerminal(__('Payment not found.'));
                }
                $this->_payment = $paymentModel;
            } else {
                $this->getMessageManager()->addErrorMessage(__('No payment id specified.'));
            }
        }
        return $this->_payment;
    }

    /**
     * @param $postData
     * @param null $payment
     * @return array
     */
    private function _getPaymentAttributes($postData, $payment = null)
    {
        $attributes = [
            PensoPayHelperCheckout::IS_VIRTUAL_TERMINAL => true,
            'AMOUNT' => $postData['amount'] * 100,
            'CURRENCY' => $postData['currency'],
            'LANGUAGE' => $postData['locale_code'],
            'EMAIL' => $postData['customer_email'],
            'CUSTOMER_NAME' => $postData['customer_name'],
            'CUSTOMER_EMAIL' => $postData['customer_email'],
            'CUSTOMER_STREET' => $postData['customer_street'],
            'CUSTOMER_ZIPCODE' => $postData['customer_zipcode'],
            'CUSTOMER_CITY' => $postData['customer_city'],
            'AUTOCAPTURE' => $postData['autocapture'],
            'AUTOFEE' => $postData['autofee'],
            'PAYMENT_METHOD' => 'default'
        ];

        if ($payment) {
            $attributes['INCREMENT_ID'] = $payment->getOrderId();
            $attributes['ORDER_ID'] = $payment->getReferenceId();
        } else {
            $attributes['INCREMENT_ID'] =$postData['order_id'];
            $attributes['ORDER_ID'] = $postData['order_id'];
        }
        return $attributes;
    }

    protected function _createPaymentLink($sendEmail)
    {
        /** @var RequestInterface $request */
        $request = $this->getRequest();
        $postData = $request->getParams();

        $attributes = $this->_getPaymentAttributes($postData);

        if (!$attributes) {
            $this->messageManager->addErrorMessage(__('Could not create payment object.'));
            return false;
        }

        try {
            $payment = $this->_payAdapter->authorizeAndCreatePaymentLink($attributes);
            if ($payment === true) {
                throw new \Exception(__('Error creating payment.'));
            }
            $paymentLink = $payment['link'];
            $this->_getSession()->setPaymentLink($paymentLink);
            $this->messageManager->addSuccessMessage($paymentLink);

            /** @var Payment $newPayment */
            $newPayment = $this->_paymentFactory->create();

            if ($sendEmail) {
                $this->_pensoPayHelper->sendEmail($postData['customer_email'], $postData['customer_name'] ?: '', $newPayment->getAmount(), $newPayment->getCurrency(), $paymentLink);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return true;
    }

    protected function _updatePaymentLink($sendEmail)
    {
        /** @var RequestInterface $request */
        $request = $this->getRequest();
        $postData = $request->getParams();

        $incId = $request->getParam('id');
        /** @var Payment $paymentModel */
        $paymentModel = $this->_paymentFactory->create();
        if (!empty($incId)) { //Existing payment
            $paymentModel->load($incId);
            if (!$paymentModel->getId()) {
                return false;
            }
        }

        $attributes = $this->_getPaymentAttributes($postData, $paymentModel);
        if (!$attributes) {
            $this->messageManager->addErrorMessage(__('Could not create payment object.'));
            return false;
        }

        try {
            $attributes['payment_id'] = $paymentModel->getId();
            $payment = $this->_payAdapter->updatePaymentAndPaymentLink($attributes);
            if ($payment === true) {
                throw new \Exception(__('Error creating payment.'));
            }
            $paymentLink = $payment['link'];
            $this->_getSession()->setPaymentLink($paymentLink);
            $this->messageManager->addSuccessMessage($paymentLink);

            if ($sendEmail) {
                $this->_pensoPayHelper->sendEmail($postData['customer_email'], $postData['customer_name'] ?: '', $paymentModel->getAmount(), $paymentModel->getCurrency(), $paymentLink);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return true;
    }

    protected function _genericPaymentCallback($action)
    {
        $paymentModel = $this->_getPayment();
        if ($paymentModel) {
            try {
                if (in_array($action, ['capture', 'refund'])) {
                    $payment = $this->_payAdapter->{$action}([
                        'TXN_ID' => $paymentModel->getReferenceId(),
                        'AMOUNT' => $paymentModel->getAmount() * 100
                    ]);
                } else {
                    $payment = $this->_payAdapter->{$action}([
                        'TXN_ID' => $paymentModel->getReferenceId()
                    ]);
                }
                if (isset($payment['errors']) && count($payment['errors'])) {
                    foreach ($payment['errors'] as $errorType => $subErrors) {
                        foreach ($subErrors as $subError) { //each error type can have multiple fails
                            $this->getMessageManager()->addErrorMessage(sprintf(
                                'Order ID: %s - %s %s',
                                $paymentModel->getOrderId(),
                                $errorType,
                                $subError
                            ));
                        }
                    }
                    throw new \Exception('Validation error(s) occured.');
                }

                $this->getMessageManager()->addSuccessMessage(
                    __('Successfully processed Order ID: ') . $paymentModel->getOrderId()
                );
                $paymentModel->importFromRemotePayment($payment);
                $paymentModel->save();
            } catch (\Exception $e) {
                if ($this->_redirect) {
                    return $this->_redirectToTerminal($e->getMessage());
                }
                $this->getMessageManager()->addErrorMessage($e->getMessage());
                return false;
            }
        }

        if ($this->_redirect) {
            return $this->_redirectToTerminal();
        }
        return true;
    }

    protected function _genericMassPaymentAction($action)
    {
        /** @var RequestInterface $request */
        $request = $this->getRequest();

        $ids = $request->getParam('selected');
        if (!empty($ids)) {
            /** @var Collection $paymentCollection */
            $paymentCollection = $this->_paymentCollectionFactory->create();
            $paymentCollection->addFieldToFilter('id', ['in' => $ids]);

            $this->_redirect = false;

            if (!empty($paymentCollection->getItems())) {
                /** @var Payment $payment */
                foreach ($paymentCollection as $payment) {
                    if ($payment->{'can' . ucfirst($action)}()) { //canCapture, canCancel, canRefund
                        $this->_payment = $payment;
                        $this->_genericPaymentCallback($action);
                    }
                }
            } else {
                $this->getMessageManager()->addErrorMessage(__('No payments found.'));
            }
        }
        return $this->_redirectToTerminal();
    }

    public function execute()
    {
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('PensoPay_Payment::virtualterminal');
    }
}
