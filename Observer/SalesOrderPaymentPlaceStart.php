<?php

namespace PensoPay\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PensoPay\Payment\Model\Ui\Method\AnydayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\MobilePayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\PensoPayConfigProvider;
use PensoPay\Payment\Model\Ui\Method\ViabillConfigProvider;
use PensoPay\Payment\Model\Ui\Method\DankortConfigProvider;
use PensoPay\Payment\Model\Ui\Method\KlarnaPaymentsConfigProvider;
use PensoPay\Payment\Model\Ui\Method\PayPalConfigProvider;
use PensoPay\Payment\Model\Ui\Method\VippsConfigProvider;

class SalesOrderPaymentPlaceStart implements ObserverInterface
{
    /**
     * Prevent order emails from being sent prematurely
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $observer->getPayment();

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
            /** @var \Magento\Sales\Model\Order $order */
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false)
                  ->setIsCustomerNotified(false)
                  ->save();
        }
    }
}
