<?php

namespace PensoPay\Payment\Observer;

use Magento\Framework\Event\Observer;
use PensoPay\Payment\Model\Ui\Method\MobilePayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\PensoPayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\ViabillConfigProvider;

class SalesOrderPaymentPlaceStart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Prevent order emails from being sent prematurely
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $observer->getPayment();

        if (in_array($payment->getMethod(), [
            PensoPayConfigProvider::CODE,
            ViabillConfigProvider::CODE,
            MobilePayConfigProvider::CODE
        ], false)) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false)
                  ->setIsCustomerNotified(false)
                  ->save();
        }
    }
}
