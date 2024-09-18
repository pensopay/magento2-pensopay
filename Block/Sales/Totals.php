<?php

namespace PensoPay\Payment\Block\Sales;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Template;
use PensoPay\Payment\Helper\Data;

class Totals extends Template
{
    protected Data $helper;

    protected DataObjectFactory $dataObjectFactory;

    public function __construct(
        Template\Context $context,
        Data $helper,
        DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct($context, $data);
    }

    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $source = $this->getSource();

        if ($source->getCardSurcharge() == 0) {
            return $this;
        }

        $cardSurchargeTitle = $this->helper->getTransactionFeeLabel();

        $cardSurchargeExclTaxTotal = [
            'code' => 'card_surcharge',
            'strong' => false,
            'value' => $source->getCardSurcharge(),
            'base_value' => $source->getBaseCardSurcharge(),
            'label' => $cardSurchargeTitle,
        ];

        /**
         * 'value' => 0 - $source->getCardSurcharge(),
         * 'base_value' => 0 - $source->getBaseCardSurcharge(),
         * 'label' => $cardSurchargeTitle . ' ' . __('is not refundable.'),
         */

        $parent->addTotal($this->dataObjectFactory->create()->setData($cardSurchargeExclTaxTotal), 'shipping');

        return $this;
    }
}
