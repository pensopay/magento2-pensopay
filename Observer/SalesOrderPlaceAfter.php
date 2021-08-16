<?php

namespace PensoPay\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order as OrderAlias;
use PensoPay\Payment\Helper\Data;
use PensoPay\Payment\Model\Ui\Method\AnydayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\MobilePayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\PensoPayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\ViabillConfigProvider;
use PensoPay\Payment\Model\Ui\Method\DankortConfigProvider;
use PensoPay\Payment\Model\Ui\Method\KlarnaPaymentsConfigProvider;
use PensoPay\Payment\Model\Ui\Method\PayPalConfigProvider;
use PensoPay\Payment\Model\Ui\Method\VippsConfigProvider;

class SalesOrderPlaceAfter implements ObserverInterface
{
    /** @var Data $_pensoPayHelper */
    protected $_pensoPayHelper;

    public function __construct(
        Data $pensoPayHelper
    ) {
        $this->_pensoPayHelper = $pensoPayHelper;
    }

    /**
     * Prevent order emails from being sent prematurely
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var OrderAlias $order */
        $order = $observer->getOrder();
        
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $order->getPayment();

        if (in_array($payment->getMethod(), [
            PensoPayConfigProvider::CODE,
            ViabillConfigProvider::CODE,
            AnydayConfigProvider::CODE,
            MobilePayConfigProvider::CODE,
            DankortConfigProvider::CODE,
            KlarnaPaymentsConfigProvider::CODE,
            PayPalConfigProvider::CODE,
            VippsConfigProvider::CODE
        ], false)) {
            /** @var OrderAlias $order */
            $this->_pensoPayHelper->setNewOrderStatus($order, true);
            $order->setCanSendNewEmailFlag(false)
                  ->setIsCustomerNotified(false)
                  ->save();
        }
    }
}
