<?php

namespace PensoPay\Payment\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Surcharge extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setCardSurcharge(0);
        $invoice->setBaseCardSurcharge(0);

        $order = $invoice->getOrder();

        if ($order->getInvoiceCollection()->getTotalCount()) {
            return $this;
        }

        $cardSurcharge = $order->getCardSurcharge();
        $baseCardSurcharge = $order->getBaseCardSurcharge();

        if ($cardSurcharge != 0) {
            $invoice->setCardSurcharge($cardSurcharge);
            $invoice->setBaseCardSurcharge($baseCardSurcharge);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $cardSurcharge);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseCardSurcharge);
        }

        return $this;
    }
}
