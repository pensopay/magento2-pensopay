<?php

namespace PensoPay\Payment\Controller\Adminhtml\Auxiliary;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;
use Magento\Sales\Controller\Adminhtml\Order as OrderController;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Api\OrderManagementInterface;

class Masscapture extends Action
{
    /** @var OrderRepository $_orderRepository */
    protected $_orderRepository;

    /** @var Transaction $_transaction */
    protected $_transaction;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        Transaction $transaction
    ) {
        parent::__construct($context);
        $this->_orderRepository = $orderRepository;
        $this->_transaction = $transaction;
    }

    public function execute()
    {
        $orderIds = $this->getRequest()->getParam('selected', array());
        $success = 0;
        foreach ($orderIds as $orderId) {
            try {
                /** @var Order $order */
                $order = $this->_orderRepository->get($orderId);

                $method = $order->getPayment()->getMethod();
                if (strpos($method, 'pensopay') === false) {
                    $this->messageManager->addErrorMessage(__('%1 Order was not placed using PensoPay', $order->getIncrementId()));
                    continue;
                }

                if (!$order->canInvoice()) {
                    $this->messageManager->addErrorMessage(__('Could not create invoice for %1', $order->getIncrementId()));
                    continue;
                }

                /** @var Invoice $invoice */
                $invoice = $order->prepareInvoice();

                if (!$invoice->getTotalQty()) {
                    $this->messageManager->addErrorMessage(__('Cannot create an invoice without products for %1.', $order->getIncrementId()));
                    continue;
                }

                $invoice->register();
                $invoice->capture();
                $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                $this->_transaction->addObject($invoice)->addObject($order)->save();
                $success++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        if ($success) {
            $this->messageManager->addSuccessMessage(__('%1 orders invoiced and captured', $success));
        }
        return $this->_redirect('sales/order/index');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(OrderController::ADMIN_RESOURCE);
    }
}
