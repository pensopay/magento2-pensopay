<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class ReturnAction extends Action
{
    /** @var Session $_session */
    protected $_session;

    /** @var OrderFactory $_orderFactory */
    protected $_orderFactory;

    protected $_encryptor;

    public function __construct(
        Context $context,
        Session $session,
        OrderFactory $orderFactory,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);

        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->_encryptor = $encryptor;
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
                $orderIncrementId = $this->_encryptor->decrypt($orderHash);

                /** @var Order $order */
                $order = $this->_orderFactory->create();
                $order->loadByIncrementId($orderIncrementId);

                if ($order->getIncrementId() === $orderIncrementId) {
                    $this->_session->setLastSuccessQuoteId($order->getQuoteId());
                    $this->_session->setLastQuoteId($order->getQuoteId());
                    $this->_session->setLastRealOrderId($order->getIncrementId());
                    $this->_session->setLastOrderId($order->getIncrementId());
                }
            }
        }
        $this->_redirect('checkout/onepage/success');
    }
}