<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Icube\TierPrice\Model\Total\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Zend_Db;

class TierPrice extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData = null;

    /**
     * @var ResourceConnection
     */
    protected $connection;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;
        $this->setCode('icube_tier_price');
    }

    /**
     * Collect customer balance totals for specified address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return Customerbalance
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $items = $shippingAssignment->getItems();

        if (!count($items)) {
            return $this;
        }

        $this->getDiscount($quote, $total);

        return $this;
    }

    /**
     * Return shopping cart total row items
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Total $total
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return [
            'code' => $this->getCode(),
            'title' => __('Tier Price'),
            'value' => -$this->getDiscount($quote, $total)
        ];
    }

    private function getDiscount(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        if ($quote->getAllVisibleItems()) {
            $discount = 0;
            $applyToPrice = false;
            foreach ($quote->getAllVisibleItems() as $item) {
                $tierPriceData = current(json_decode($item->getTierPriceData() ?? '', true) ?? []);
                $tmp = 0;
                $tmpPercent = 0;
                $qty = $item->getQtyTierPrice();

                if (!empty($tierPriceData)) {
                    if ($tierPriceData['discount_percentage'] && ($tierPriceData['discount_percentage'] > 0)) {
                        $tmpPercent = $tierPriceData['discount_percentage'];
                        $tmp = ((int) $item->getPrice() * $tierPriceData['discount_percentage']) / 100;
                    } elseif ($tierPriceData['discount_amount']) {
                        $tmp = $tierPriceData['discount_amount'];
                    } else {
                        $tmp = 0;
                    }

                    $applyToPrice = (bool) $tierPriceData['apply_to_price'];
                    $tmp = round($tmp);

                    if (!$applyToPrice) {
                        $tmp = $tmp * $qty;
                        $item->setDiscountPercent($item->getDiscountPercent() + $tmpPercent);
                        $item->setDiscountAmount($item->getDiscountAmount() + $tmp);
                        $item->setBaseDiscountAmount($item->getBaseDiscountAmount() + $tmp);
                    
                        $discount += $tmp;
                    }
                }
            }

            if (!$applyToPrice) {
                $discountAmount = -$discount;
                $total->setSubtotalWithDiscount($total->getSubtotalWithDiscount() + $discountAmount);
                $total->setBaseSubtotalWithDiscount($total->getBaseSubtotalWithDiscount() + $discountAmount);
                $total->setTotalAmount('tier_price', $discountAmount);
                $total->setBaseTotalAmount('tier_price', $discountAmount);
            }

            return $discount;
        }
        
        return 0;
    }
}
