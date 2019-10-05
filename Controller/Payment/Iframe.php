<?php

namespace PensoPay\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Iframe extends Action
{
    /** @var PageFactory $_pageFactory */
    protected $_pageFactory;

    /**
     * Class constructor
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * Display iFrame page
     */
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
