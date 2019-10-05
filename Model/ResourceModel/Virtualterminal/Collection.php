<?php

namespace PensoPay\Payment\Model\ResourceModel\Virtualterminal;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    protected $request;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        RequestInterface $request,
        $mainTable = 'pensopay_payment',
        $resourceModel = '\PensoPay\Payment\Model\ResourceModel\Payment'
    ) {
        $this->request = $request;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    public function _beforeLoad()
    {
        $this->addFieldToFilter('is_virtualterminal', 1);
        return parent::_beforeLoad();
    }
}