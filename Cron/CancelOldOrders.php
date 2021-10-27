<?php

namespace PensoPay\Payment\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use PensoPay\Payment\Model\Payment;
use PensoPay\Payment\Model\PaymentFactory;
use PensoPay\Payment\Model\ResourceModel\Payment\Collection;
use Psr\Log\LoggerInterface;

class CancelOldOrders
{
    /** @var Collection $_paymentCollection */
    protected $_paymentCollection;

    /** @var ScopeConfigInterface $_scopeConfig */
    protected $_scopeConfig;

    /** @var CollectionFactory $_orderCollectionFactory */
    protected $_orderCollectionFactory;

    /** @var OrderRepository $_orderRepository */
    protected $_orderRepository;

    /** @var PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    /** @var LoggerInterface $_logger */
    protected $_logger;

    /**
     * CancelOldOrders constructor.
     * @param Collection $paymentCollection
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Collection $paymentCollection,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        PaymentFactory $paymentFactory,
        LoggerInterface $logger
    ) {
        $this->_paymentCollection = $paymentCollection;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderRepository = $orderRepository;
        $this->_paymentFactory = $paymentFactory;
        $this->_logger = $logger;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        //Disabled from admin
        if (!$this->_scopeConfig->isSetFlag('payment/pensopay/pending_payment_order_cancel', ScopeInterface::SCOPE_STORE)) {
            return $this;
        }

        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->_orderCollectionFactory->create();
        $collection->addFieldToFilter('state', 'new');
        $collection->addFieldToFilter('status', 'pending');
        $collection->getSelect()->join(
            array('payments' => $collection->getTable('sales_order_payment')),
            'payments.parent_id = main_table.entity_id',
            'method'
        );
        $collection->addFieldToFilter('method', array('like' => 'pensopay%'));
        $collection->getSelect()->where('HOUR(TIMEDIFF(NOW(), created_at)) >= 24');

        /** @var Order $order */
        foreach ($collection as $order) {
            try {
                if ($order->canCancel()) {
                    $order->cancel();
                    $this->_logger->info('CRON: Canceled old order #' . $order->getIncrementId());
                } else {
                    throw new \Exception('Order is in a non-cancellable state.');
                }
            } catch (CommandException $commandException) {
                $this->_logger->error('CRON: Could not cancel payment for order #' . $order->getIncrementId() . ' Exception: ' . $commandException->getMessage());
            } catch (\Exception $e) {
                $this->_logger->error('CRON: Could not cancel old order #' . $order->getIncrementId() . ' Exception: ' . $e->getMessage());
            } finally {
                try {
                    $this->_orderRepository->save($order);

                    /** \PensoPay\Payment\Model\Payment $paymentModel */
                    $paymentModel = $this->_paymentFactory->create();
                    $paymentModel->load($order->getIncrementId(), 'order_id');

                    if ($paymentModel->getId() && !$paymentModel->getIsVirtualterminal()) {
                        $paymentModel->updatePaymentRemote(); //make sure we have the latest status
                    }
                } catch (\Exception $e) {}
            }
        }
        return $this;
    }
}
