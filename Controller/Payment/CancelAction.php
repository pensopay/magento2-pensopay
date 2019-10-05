<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Context;
use PensoPay\Payment\Helper\Checkout;

class CancelAction extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Checkout
     */
    private $_checkoutHelper;

    /**
     * @param Context $context
     * @param Checkout $checkoutHelper
     */
    public function __construct(
        Context $context,
        Checkout $checkoutHelper
    ) {
        parent::__construct($context);
        $this->_checkoutHelper = $checkoutHelper;
    }

    /**
     * Customer canceled payment on gateway side.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->_checkoutHelper->cancelCurrentOrder('');
        $this->_checkoutHelper->restoreQuote();

        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
