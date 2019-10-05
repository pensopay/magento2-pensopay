<?php

namespace PensoPay\Payment\Block\Adminhtml\Virtualterminal;

use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;

class CreatePayment extends Container
{
    /** @var \Magento\Backend\Model\UrlInterface $_urlInterface */
    protected $_urlInterface;

    public function __construct(
        Context $context,
        \Magento\Backend\Model\UrlInterface $urlInterface,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_urlInterface = $urlInterface;
    }

    protected function _prepareLayout()
    {
        $addButtonProps = [
            'id' => 'create_new_payment',
            'label' => __('New Payment'),
            'class' => 'primary add',
            'button_class' => '',
            'class_name' => Button::class,
            'onclick' => 'setLocation(\'' . $this->getUrl('pensopay/virtualterminal/edit') . '\')'
        ];

        $url = $this->getUrl('pensopay/payment/edit');
        $this->buttonList->add('add_new', $addButtonProps);

        return parent::_prepareLayout();
    }
}
