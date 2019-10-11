<?php

namespace PensoPay\Payment\Cron;

use PensoPay\Payment\Model\Payment;
use PensoPay\Payment\Model\ResourceModel\Payment\Collection;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class UpdatePayments
{
    /** @var Collection $_paymentCollection */
    protected $_paymentCollection;

    public function __construct(
        Collection $paymentCollection
    ) {
        $this->_paymentCollection = $paymentCollection;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        //Ease of use over custom logger
        $writer = new Stream(BP . '/var/log/pensopay-payments.log');
        $logger = new Logger();
        $logger->addWriter($writer);

        $this->_paymentCollection->addFieldToFilter('state', ['nin' => Payment::FINALIZED_STATES]);
        $this->_paymentCollection->addFieldToFilter('reference_id', ['notnull' => true]);
        $this->_paymentCollection->addFieldToFilter('is_virtualterminal', 1);

        /** @var Payment $payment */
        foreach ($this->_paymentCollection as $payment) {
            try {
                $payment->updatePaymentRemote();
            } catch (\Exception $e) {
                $logger->info('CRON: Could not update payment remotely. Exception: ' . $e->getMessage());
            }
        }
        return $this;
    }
}
