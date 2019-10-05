<?php

namespace PensoPay\Payment\Ui\Component\Virtualterminal\Grid\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Paymentlink extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Batch constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $value = $item['link'];
                $link = [
                    'target' => '_blank',
                    'class' => 'payment-link',
                    'hidden' => false,
                ];
                if ($value === 'Array' || empty($value) || is_array($value)) { //in case of a corrupted link in the database, show an error
                    $link['href'] = $this->urlBuilder->getUrl('*/*/*');
                    $link['label'] = __('Error');
                } else {
                    $link['href'] = $value;
                    $link['label'] = __('Link');
                }
                $item[$this->getData('name')]['payment_link'] = $link;
            }
        }

        return $dataSource;
    }
}
