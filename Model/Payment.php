<?php

namespace PensoPay\Payment\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use PensoPay\Payment\Helper\Checkout as PensoPayCheckoutHelper;
use PensoPay\Payment\Helper\Data as PensoPayDataHelper;
use PensoPay\Payment\Model\Adapter\PensoPayAdapter;

class Payment extends AbstractModel
{
    const PAYMENT_TABLE = 'pensopay_payment';

    /** @var PensoPayCheckoutHelper $_helper */
    protected $_helper;

    /** @var PensoPayDataHelper $_pensoPayHelper */
    protected $_pensoPayHelper;

    /** @var OrderRepository $_orderRepository */
    protected $_orderRepository;

    /** @var SearchCriteriaBuilder $_searchCriteriaBuilder */
    protected $_searchCriteriaBuilder;

    /** @var Adapter\PensoPayAdapter $_paymentAdapter */
    protected $_paymentAdapter;

    const STATE_INITIAL = 'initial';
    const STATE_NEW = 'new';
    const STATE_PROCESSED = 'processed';
    const STATE_PENDING = 'pending';
    const STATE_REJECTED = 'rejected';

    const STATUS_APPROVED = 20000;
    const STATUS_WAITING_APPROVAL = 20200;
    const STATUS_3D_SECURE_REQUIRED = 30100;
    const STATUS_REJECTED_BY_ACQUIRER = 40000;
    const STATUS_REQUEST_DATA_ERROR = 40001;
    const STATUS_AUTHORIZATION_EXPIRED = 40002;
    const STATUS_ABORTED = 40003;
    const STATUS_GATEWAY_ERROR = 50000;
    const COMMUNICATIONS_ERROR_ACQUIRER = 50300;

    const OPERATION_CAPTURE = 'capture';
    const OPERATION_AUTHORIZE = 'authorize';
    const OPERATION_CANCEL = 'cancel';
    const OPERATION_REFUND = 'refund';
    const OPERATION_MOBILEPAY_SESSION = 'session';

    const FRAUD_PROBABILITY_HIGH = 'high';
    const FRAUD_PROBABILITY_NONE = 'none';

    protected $_lastOperation = [];

    const STATUS_CODES =
        [
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_WAITING_APPROVAL => 'Waiting approval',
            self::STATUS_3D_SECURE_REQUIRED => '3D Secure is required',
            self::STATUS_REJECTED_BY_ACQUIRER => 'Rejected By Acquirer',
            self::STATUS_REQUEST_DATA_ERROR => 'Request Data Error',
            self::STATUS_AUTHORIZATION_EXPIRED => 'Authorization expired',
            self::STATUS_ABORTED => 'Aborted',
            self::STATUS_GATEWAY_ERROR => 'Gateway Error',
            self::COMMUNICATIONS_ERROR_ACQUIRER => 'Communications Error (with Acquirer)'
        ];

    /**
     * States in which the payment can't be updated anymore
     * Used for cron.
     */
    const FINALIZED_STATES =
        [
            self::STATE_REJECTED,
            self::STATE_PROCESSED
        ];

    /**
     * Payment constructor.
     * @param Context $context
     * @param Registry $registry
     * @param PensoPayCheckoutHelper $checkoutHelper
     * @param PensoPayDataHelper $pensoPayDataHelper
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Adapter\PensoPayAdapter $paymentAdapter
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PensoPayCheckoutHelper $checkoutHelper,
        PensoPayDataHelper $pensoPayDataHelper,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PensoPayAdapter $paymentAdapter,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_init(\PensoPay\Payment\Model\ResourceModel\Payment::class);
        $this->_helper = $checkoutHelper;
        $this->_pensoPayHelper = $pensoPayDataHelper;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_paymentAdapter = $paymentAdapter;
    }

    public function getDisplayStatus()
    {
        $lastCode = $this->getLastCode();

        $status = '';
        if ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_CAPTURE) {
            $status = __('Captured');
        } elseif ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_CANCEL) {
            $status = __('Cancelled');
        } elseif ($lastCode == self::STATUS_APPROVED && $this->getLastType() == self::OPERATION_REFUND) {
            $status = __('Refunded');
        } elseif (!empty(self::STATUS_CODES[$lastCode])) {
            $status = self::STATUS_CODES[$lastCode];
        }
        return sprintf('%s (%s)', $status, $this->getState());
    }

    public function getMetadata()
    {
        if (!empty($this->getData('metadata'))) {
            return json_decode($this->getData('metadata'), true);
        }
        return [];
    }

    public function getFirstOperation()
    {
        if (!empty($this->getOperations())) {
            $operations = json_decode($this->getOperations(), true);
            if (!empty($operations) && is_array($operations)) {
                $firstOp = array_shift($operations);
                if (!empty($firstOp) && is_array($firstOp)) {
                    return [
                        'type' => $firstOp['type'],
                        'code' => $firstOp['qp_status_code'],
                        'msg' => $firstOp['qp_status_msg']
                    ];
                }
            }
        }
        return [];
    }

    public function getLastOperation()
    {
        if (empty($this->_lastOperation)) {
            if (!empty($this->getOperations())) {
                $operations = json_decode($this->getOperations(), true);
                if (!empty($operations) && is_array($operations)) {
                    $lastOp = array_pop($operations);
                    if (!empty($lastOp) && is_array($lastOp)) {
                        $this->_lastOperation = [
                            'type' => $lastOp['type'],
                            'code' => $lastOp['qp_status_code'],
                            'msg' => $lastOp['qp_status_msg']
                        ];
                    }
                }
            }
        }
        return $this->_lastOperation;
    }

    public function getLastMessage()
    {
        return isset($this->getLastOperation()['msg']) ? $this->getLastOperation()['msg'] : '';
    }

    public function getLastType()
    {
        return isset($this->getLastOperation()['type']) ? $this->getLastOperation()['type'] : '';
    }

    public function getLastCode()
    {
        return isset($this->getLastOperation()['code']) ? $this->getLastOperation()['code'] : '';
    }

    /**
     * @param array $payment
     */
    public function importFromRemotePayment($payment)
    {
        if (!$this->_pensoPayHelper->getIsTestmode() && $payment['test_mode']) {
            $this->setState(self::STATE_REJECTED);
            return;
        }

        $this->setReferenceId($payment['id']);
        unset($payment['id']); //We don't want to override the object id with the remote id
        $this->addData($payment);
        if (isset($payment['link']) && !empty($payment['link'])) {
            if (is_array($payment['link'])) {
                $this->setLink($payment['link']['url']);
            } else {
                $this->setLink($payment['link']);
            }
        }

        $amount = 0;
        foreach ($payment['basket'] as $item) {
            $amount += $item['item_price'];
        }
        if (isset($payment['shipping']['amount'])) {
            $amount += $payment['shipping']['amount'];
        }
        $this->setAmount($amount / 100);
        $this->setCurrencyCode($payment['currency']);
        if (!empty($payment['metadata']) && is_array($payment['metadata'])) {
            $this->setFraudProbability($payment['metadata']['fraud_suspected'] || $payment['metadata']['fraud_reported'] ? self::FRAUD_PROBABILITY_HIGH : self::FRAUD_PROBABILITY_NONE);
        }
        $this->setOperations(json_encode($payment['operations']));
        $this->setMetadata(json_encode($payment['metadata']));
        $this->setHash(md5($this->getReferenceId() . $this->getLink() . $this->getAmount()));

        if (!empty($payment['operations'])) {
            $amountCaptured = 0;
            $amountRefunded = 0;
            foreach ($payment['operations'] as $operation) {
                if ($operation['type'] === 'capture') {
                    $amountCaptured += $operation['amount'];
                } elseif ($operation['type'] === 'refund') {
                    $amountRefunded += $operation['amount'];
                }
            }
            $this->setAmountCaptured($amountCaptured / 100);
            $this->setAmountRefunded($amountRefunded / 100);
        }
    }

    /**
     * Updates payment data from remote gateway.
     *
     * @throws \Exception
     */
    public function updatePaymentRemote()
    {
        if (!$this->getId()) {
            throw new \Exception(__('Payment not loaded.'));
        }

        if (!$this->getReferenceId()) {
            throw new \Exception(__('Reference id not found.'));
        }

        $orderIncrement = $this->getOrderId();
        $storeId = null;
        if (!empty($orderIncrement)) {
            $storeId = $this->_pensoPayHelper->getStoreIdForOrderIncrement($orderIncrement);
            if (is_numeric($storeId)) {
                $this->_paymentAdapter->setTransactionStore($storeId);
            }
        }
        $paymentInfo = $this->_paymentAdapter->getPayment($this->getReferenceId());
        $this->importFromRemotePayment($paymentInfo);
        $this->save();
    }

    public function canCapture()
    {
        return $this->getState() === self::STATE_NEW;
    }

    public function canCancel()
    {
        return $this->getState() === self::STATE_NEW;
    }

    public function canRefund()
    {
        return ($this->getState() === self::STATE_PROCESSED && ($this->getAmount() !== $this->getAmountRefunded()));
    }
}
