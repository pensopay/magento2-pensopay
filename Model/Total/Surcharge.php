<?php

namespace PensoPay\Payment\Model\Total;

use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use PensoPay\Payment\Helper\Data;

class Surcharge extends Address\Total\AbstractTotal
{
    protected Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $total->setTotalAmount($this->getCode(), 0);
        $total->setBaseTotalAmount($this->getCode(), 0);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        //Basically persist this when retroactively setting it on the quote in the callback, which is the only place we are aware of this.
        $baseFee = $quote->getBaseCardSurcharge();
        $fee = $quote->getCardSurcharge();

        $total->setTotalAmount($this->getCode(), $fee);
        $total->setBaseTotalAmount($this->getCode(), $baseFee);

        $total->setCardSurcharge($fee);
        $total->setBaseCardSurcharge($baseFee);

        return $this;
    }

    public function fetch(Quote $quote, Total $total)
    {
        $fee = $total->getCardSurcharge();

        return [
            [
                'code' => $this->getCode(),
                'title' => __($this->helper->getTransactionFeeLabel()),
                'value' => $fee
            ]
        ];
    }

    public function getLabel()
    {
        return __($this->helper->getTransactionFeeLabel());
    }
}
