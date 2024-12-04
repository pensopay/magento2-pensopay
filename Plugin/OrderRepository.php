<?php

namespace PensoPay\Payment\Plugin;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepository
{
    protected OrderExtensionFactory $_extensionFactory;

    public function __construct(
        OrderExtensionFactory $extensionFactory
    ) {
        $this->_extensionFactory = $extensionFactory;
    }

    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->_extensionFactory->create();
        $extensionAttributes->setCardSurcharge($order->getData('card_surcharge'));
        $order->setExtensionAttributes($extensionAttributes);

        return $order;
    }

    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult)
    {
        $orders = $searchResult->getItems();

        foreach ($orders as $order) {
            $extensionAttributes = $order->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->_extensionFactory->create();
            $extensionAttributes->setCardSurcharge($order->getData('card_surcharge'));
            $order->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }
}
