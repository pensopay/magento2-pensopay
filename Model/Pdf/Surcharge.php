<?php

namespace PensoPay\Payment\Model\Pdf;

use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

class Surcharge extends DefaultTotal
{
    protected \PensoPay\Payment\Helper\Data $helper;

    public function __construct(
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        \PensoPay\Payment\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
    }

    public function getTotalsForDisplay()
    {
        $totals = [];
        $cardSurcharge = $this->getSource()->getCardSurcharge();
        if ($cardSurcharge != 0) {
            $amount = $this->getOrder()->formatPriceTxt($cardSurcharge);
            $defaultLabel = $this->helper->getTransactionFeeLabel();
            $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;

            $label = $defaultLabel;
            $totals[] = [
                'amount' => $this->getAmountPrefix() . $amount,
                'label' => $label . ':',
                'font_size' => $fontSize
            ];
        }

        return $totals;
    }
}
