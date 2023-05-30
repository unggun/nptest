<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\TierPrice\Model\Resolver;

use Icube\TierPrice\Helper\Data as TierPriceHelper;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\CatalogCustomerGraphQl\Model\Resolver\Customer\GetCustomerGroup;
use Magento\CatalogCustomerGraphQl\Model\Resolver\Product\Price\TiersFactory;
use Magento\CatalogGraphQl\Model\Resolver\Product\Price\Discount;
use Magento\CatalogGraphQl\Model\Resolver\Product\Price\ProviderPool as PriceProviderPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Resolver for price_tiers
 */
class ProductTierPrices implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var GetCustomerGroup
     */
    private $getCustomerGroup;

    /**
     * @var int
     */
    private $customerGroupId;

    /**
     * @var Discount
     */
    private $discount;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var TierPriceHelper
     */
    private $tierPriceHelper;

    /**
     * @var array
     */
    private $formatAndFilterTierPrices = [];

    /**
     * @var array
     */
    private $tierPricesQty = [];

    /**
     * @param ValueFactory $valueFactory
     * @param TiersFactory $tiersFactory
     * @param GetCustomerGroup $getCustomerGroup
     * @param Discount $discount
     * @param PriceProviderPool $priceProviderPool
     * @param PriceCurrencyInterface $priceCurrency
     * @param TierPriceHelper $tierPriceHelper
     */
    public function __construct(
        ValueFactory $valueFactory,
        GetCustomerGroup $getCustomerGroup,
        Discount $discount,
        PriceCurrencyInterface $priceCurrency,
        TierPriceHelper $tierPriceHelper
    ) {
        $this->valueFactory = $valueFactory;
        $this->getCustomerGroup = $getCustomerGroup;
        $this->discount = $discount;
        $this->priceCurrency = $priceCurrency;
        $this->tierPriceHelper = $tierPriceHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $product = $value['model'];
        if ($product->hasData('can_show_price') && $product->getData('can_show_price') === false) {
            return [];
        }

        $customerId = (int) $context->getUserId();
        if ($customerId != null) {
            return $this->valueFactory->create(
                function () use ($product, $context) {
                    $this->resetFormatAndFilterTierPrices();
                    $currencyCode = $context->getExtensionAttributes()->getStore()->getCurrentCurrencyCode();
                    $productPrice = $product->getPriceInfo()->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue() ?? 0.0;
                    if ($productPrice < $product->getPrice()) {
                        // check whether product regular price has been calculated,
                        // tier price custom has apply_to_price set to true
                        return $this->formatAndFilterTierPrices;
                    }
                    $tierPrices = $this->tierPriceHelper->getTierPriceDetailsByProduct($product);
                    $this->customerGroupId = $this->getCustomerGroup->execute($context->getUserId());
                    $customerGroup = $this->tierPriceHelper->getCustomerGroupCode($this->customerGroupId)->getCode();
                    foreach ($tierPrices as $tierPrice) {
                        if ($tierPrice['price_value_type'] == 'percent') {
                            $finalPrice = $productPrice - (((int) $productPrice * $tierPrice['website_price']) / 100);
                        } else {
                            $finalPrice = $productPrice - $tierPrice['website_price'];
                        }
                        $this->formatAndFilterTierPrices[] = [
                            "discount" => [
                                'amount_off' => $tierPrice['price_value_type'] == 'percent' ? ($tierPrice['website_price'] / 100) * $productPrice : $tierPrice['website_price'],
                                'percent_off' => $tierPrice['price_value_type'] == 'percent' ? $tierPrice['website_price'] : null
                            ],
                            "quantity" => $tierPrice['price_qty'],
                            "final_price" => [
                                "value" => $this->priceCurrency->convertAndRound($finalPrice),
                                "currency" => $currencyCode
                            ],
                            "customer_group" => $tierPrice['all_groups'] == '0' ? $customerGroup : null
                        ];
                    }
                    return $this->formatAndFilterTierPrices;
                }
            );
        } else {
            return [];
        }
    }

    /**
     *  Remove all element from formatAndFilterTierPrices
     */
    private function resetFormatAndFilterTierPrices()
    {
        foreach ($this->formatAndFilterTierPrices as $key => $value) {
            unset($this->formatAndFilterTierPrices[$key]);
        }
    }
}
