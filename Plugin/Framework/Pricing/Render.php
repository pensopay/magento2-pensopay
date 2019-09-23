<?php

namespace PensoPay\Payment\Plugin\Framework\Pricing;

use Magento\Bundle\Model\Product\Type as BundleProduct;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use PensoPay\Payment\Helper\Viabill;

/**
 * Class Render
 * This class is safe to preference in order to implement your own viabill rendering logic.
 * This is the logic for product renders (list - any type, product page)
 * @package PensoPay\Payment\Plugin\Framework\Pricing
 */
class Render
{
    const PRICE_RENDERER_DEFAULT = 'product.price.render.default';

    /** @var Viabill $_viabillHelper */
    protected $_viabillHelper;

    public function __construct(
        Viabill $viabillHelper
    ) {
        $this->_viabillHelper = $viabillHelper;
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @param $priceCode
     * @param Product $saleableItem
     * @param array $arguments
     * @return mixed|string
     */
    public function aroundRender(
        $subject,
        \Closure $proceed,
        $priceCode,
        $saleableItem,
        $arguments = []
    ) {
        $html = $proceed($priceCode, $saleableItem, $arguments);
        $type = false;
        $price = false;
        $dynamicPriceSelector = '';

        if (isset($arguments['zone'])) {
            if ($arguments['zone'] === 'item_view') {
                switch ($saleableItem->getTypeId()) {
                    case BundleProduct::TYPE_CODE:
                        if (
                            ($priceCode === 'final_price' || $priceCode === 'configured_price')
                            && isset($arguments['price_render'])
                            && $arguments['price_render'] === self::PRICE_RENDERER_DEFAULT
                        ) {
                            $type = 'product';
                            if (
                                ($priceInfo = $saleableItem->getPriceInfo())
                                && ($regularPrice = $priceInfo->getPrice('regular_price'))
                                && $regularPrice->getAmount()->getValue()
                            ) {
                                $price = $regularPrice->getAmount()->getValue();

                                //This price needs to auto-renew
                                if ($priceCode === 'configured_price') {
                                    $dynamicPriceSelector = '.product-details|span.price';
                                }
                                /** else
                                    $type = null;
                                 **/
                            }
                        }
                        break;
                    case Configurable::TYPE_CODE:
                        if ($priceCode === 'tier_price') {
                            $dynamicPriceSelector = '.product-info-main|span.price';
                        }
                        //Intentional bleed through to the next case
                    case Product\Type::TYPE_SIMPLE:
                        if ($priceCode === 'tier_price'
                            && isset($arguments['price_render'])
                            && $arguments['price_render'] === self::PRICE_RENDERER_DEFAULT
                        ) {
                            $type = 'product';
                            $price = $saleableItem->getFinalPrice();
                        }
                        break;
                    default:
                        break;
                }
            } elseif ($arguments['zone'] === 'item_list') {
                $type = 'list';
                switch ($saleableItem->getTypeId()) {
                    case Grouped::TYPE_CODE:
                        $price = round($saleableItem->getMinimalPrice(), 2);
                        break;
                    case Configurable::TYPE_CODE:
                        $dynamicPriceSelector = '.product-item-details|span.price';
                        //Intentional bleed through to the next case
                    default:
                        if ($priceCode === 'final_price') {
                            $price = $saleableItem->getFinalPrice();
                        }
                        break;
                }
            }
        }

        if ($type && $price) {
            $html .= $this->_viabillHelper->renderViabillPrice($type, $price, $dynamicPriceSelector);
        }
        return $html;
    }
}
