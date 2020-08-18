<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class ReturnAction extends Action
{
    /** @var Session $_session */
    protected $_session;

    /** @var OrderFactory $_orderFactory */
    protected $_orderFactory;

    public function __construct(
        Context $context,
        Session $session,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);

        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
    }

    /**
     * Redirect to to checkout success
     *
     * @return void
     */
    public function execute()
    {
        $lastRealOrderId = $this->_session->getLastRealOrderId();

        if (!$lastRealOrderId) {
            $orderHash = $this->getRequest()->getParam('ori');
            if (!empty($orderHash)) {
                $orderIncrementId = base64_decode($orderHash);

                /** @var Order $order */
                $order = $this->_orderFactory->create();
                $order->loadByIncrementId($orderIncrementId);

                if ($order->getIncrementId() === $orderIncrementId) {
                    $this->_session->setLastSuccessQuoteId($order->getQuoteId());
                    $this->_session->setLastQuoteId($order->getQuoteId());
                    $this->_session->setLastRealOrderId($order->getIncrementId());
                    $this->_session->setLastOrderId($order->getIncrementId());
                } else {
                    die('tine, let me know about this');
                }
            }
        }
        $this->_redirect('checkout/onepage/success');
    }
}