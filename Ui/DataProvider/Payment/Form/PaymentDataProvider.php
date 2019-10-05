<?php

namespace PensoPay\Payment\Ui\DataProvider\Payment\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PensoPay\Payment\Model\ResourceModel\Payment\CollectionFactory;

class PaymentDataProvider extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var array
     */
    protected $_loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param PoolInterface $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->pool = $pool;
    }

    public function getData()
    {
        if (!isset($this->_loadedData)) {
            $items = $this->collection->getItems();
            foreach ($items as $payment) {
                $this->_loadedData[$payment->getId()] = $payment->getData();
            }
        }
        return $this->_loadedData;
    }

    public function getFirstItem()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData[0];
        }
        return false;
    }
}
