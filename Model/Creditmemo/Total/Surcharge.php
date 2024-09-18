<?php

namespace PensoPay\Payment\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Surcharge extends AbstractTotal
{
    public function collect(Creditmemo $creditmemo)
    {
        $creditmemo->setCardSurcharge(0);
        $creditmemo->setBaseCardSurcharge(0);

        $order = $creditmemo->getOrder();
        $cardSurcharge = $order->getCardSurcharge();
        $baseCardSurcharge = $order->getBaseCardSurcharge();

        if ($cardSurcharge != 0) { //It is not refundable
            $creditmemo->setCardSurcharge(0 - $cardSurcharge);
            $creditmemo->setBaseCardSurcharge(0 - $baseCardSurcharge);
        }

        return $this;
    }
}
