<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal\Buttons\Payment\Control\Container;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Generic implements ButtonProviderInterface
{
    /**
     * Url Builder
     *
     * @var Context
     */
    protected $context;

    /**
     * Registry
     *
     * @var Registry
     */
    protected $registry;

    /** @var \PensoPay\Payment\Model\PaymentFactory $_paymentFactory */
    protected $_paymentFactory;

    /** @var \PensoPay\Payment\Model\Payment $_payment */
    protected $_payment;

    /**
     * Generic constructor
     *
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        \PensoPay\Payment\Model\PaymentFactory $_paymentFactory
    ) {
        $this->context = $context;
        $this->_paymentFactory = $_paymentFactory;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrl($route, $params);
    }

    public function hasPayment()
    {
        return $this->getPayment() !== null;
    }

    public function getPayment()
    {
        if (!isset($this->_payment)) {
            $id = $this->context->getRequestParam('id');
            if ($id) {
                $this->_payment = $this->_paymentFactory->create();
                $this->_payment->load($id);
                if (!$this->_payment->getId()) {
                    $this->_payment = null; //payment doesn't exist.
                }
            }
        }
        return $this->_payment;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [];
    }
}
