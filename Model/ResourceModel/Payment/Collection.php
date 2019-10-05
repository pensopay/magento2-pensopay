<?php

namespace PensoPay\Payment\Model\ResourceModel\Payment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\PensoPay\Payment\Model\Payment::class, \PensoPay\Payment\Model\ResourceModel\Payment::class);
    }
}
