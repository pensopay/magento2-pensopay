<?php
namespace PensoPay\Payment\Block;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Gateway\ConfigInterface;
use PensoPay\Payment\Model\Payment;
use PensoPay\Payment\Model\PaymentFactory;

class Info extends ConfigurableInfo
{
    /**
     * @var string
     */
    protected $_template = 'PensoPay_Payment::info/default.phtml';

    /** @var PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        PaymentFactory $paymentFactory,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->_paymentFactory = $paymentFactory;
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Get order payment
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPayment()
    {
        if ($this->getInfo()->getOrder()) {
            /** @var Payment $payment */
            $payment = $this->_paymentFactory->create();
            $payment->load($this->getInfo()->getOrder()->getIncrementId(), 'order_id');
            if ($payment->getId()) {
                $firstOp = $payment->getFirstOperation();
                if (!empty($firstOp)) {
                    if (($firstOp['type'] == Payment::OPERATION_AUTHORIZE || $firstOp['type'] == Payment::OPERATION_MOBILEPAY_SESSION) && ($firstOp['code'] == Payment::STATUS_APPROVED || $firstOp['code'] == Payment::STATUS_3D_SECURE_REQUIRED)) {
                        return $payment;
                    }
                }
            }
        }
        return null;
    }
}
