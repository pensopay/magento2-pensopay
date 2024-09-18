<?php

namespace PensoPay\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class OnCreateOrder implements ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $cardSurcharge = $quote->getCardSurcharge();
        $baseCardSurcharge = $quote->getBaseCardSurcharge();
        if (!$cardSurcharge || !$baseCardSurcharge) {
            return $this;
        }

        $order = $observer->getOrder();
        $order->setData('card_surcharge', $cardSurcharge);
        $order->setData('base_card_surcharge', $baseCardSurcharge);

        return $this;
    }
}
