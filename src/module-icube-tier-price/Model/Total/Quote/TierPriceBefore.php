<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Icube\TierPrice\Model\Total\Quote;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Rule;
use Zend_Db;

class TierPriceBefore extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
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
        ResourceConnection $connection,
        TimezoneInterface $timezoneInterface,
        PriceCurrencyInterface $priceCurrency,
        RuleRepositoryInterface $ruleRepository,
        CustomerSession $customerSession
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;
        $this->connection = $connection;
        $this->timezoneInterface = $timezoneInterface;
        $this->ruleRepository = $ruleRepository;
        $this->customerSession = $customerSession;
        $this->setCode('icube_tier_price_before');
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

        $this->getDiscount($quote);

        return $this;
    }

    private function getDiscount(\Magento\Quote\Model\Quote $quote)
    {
        $customerId = $this->customerSession->getCustomer()->getId();

        //add fixing can't add to cart via API
        if (!$customerId) {
            $obj = \Magento\Framework\App\ObjectManager::getInstance();
            $customerContext = $obj->create('Magento\Authorization\Model\CompositeUserContext');
            if ($customerContext->getUserId()) {
                $customerId = $customerContext->getUserId();
            }
        }
        //end fixing
        
        $items = ($customerId) ? $quote->getAllVisibleItems() : $quote->getItems();

        if ($items) {
            //custom tier prices array
            $dateNow = $this->timezoneInterface->date()->format('Y-m-d');
            $connection = $this->connection->getConnection();
            $tableName = $this->connection->getTableName('icube_tier_price');
            $skus = implode(
                ",",
                array_map(
                    function ($a) {
                        return "'" . $a->getSku() . "'";
                    },
                    $items
                )
            );
            $customerId = $quote->getCustomerId();
            $customerGroup = $quote->getCustomerGroupId();
            $discount = 0;
            $appliedRuleIds = $quote->getAppliedRuleIds() ? explode(",", $quote->getAppliedRuleIds()) : [];
            foreach ($items as $item) {
                $query = "SELECT * FROM " . $tableName . " WHERE vendor_code = '". $item->getSellerId() ."' && product_sku in (".$skus.") && (start_date <= '".$dateNow."' || start_date IS NULL) && (end_date >= '".$dateNow."' || end_date IS NULL);";
                $queryResults = $connection->fetchAll(
                    $query,
                    [],
                    Zend_Db::FETCH_ASSOC
                );
                $tierPriceData = [];
                $prevRow = [];
                $prevApplyToPrice = [];
                $applyToPrice = false;
                $tmp = 0;
                $qty = $item->getQty();
                foreach ($appliedRuleIds as $appliedRuleId) {
                    $rule = $this->ruleRepository->getById((int)$appliedRuleId);
                    if ($rule->getSimpleAction() == Rule::BUY_X_GET_Y_ACTION) {
                        $x = $rule->getDiscountStep();
                        $y = $rule->getDiscountAmount();
                        if (!$x || $y > $x) {
                            break;
                        }
                        $buyAndDiscountQty = $x + $y;

                        $fullRuleQtyPeriod = floor($qty / $buyAndDiscountQty);
                        $freeQty = $qty - $fullRuleQtyPeriod * $buyAndDiscountQty;

                        $discountQty = $fullRuleQtyPeriod * $y;
                        if ($freeQty > $x) {
                            $discountQty += $freeQty - $x;
                        }

                        $qty -= $discountQty;
                    }
                }
                $product = $item->getProduct();
                $finalPrice = 0;
                if ($item->getParentItem() && $item->isChildrenCalculated()) {
                    $finalPrice = $item->getParentItem()->getProduct()->getPriceModel()->getChildFinalPrice(
                        $item->getParentItem()->getProduct(),
                        $item->getParentItem()->getQty(),
                        $product,
                        $item->getQty()
                    );
                } elseif (!$item->getParentItem()) {
                    $finalPrice = $product->getFinalPrice($item->getQty());
                }

                foreach ($queryResults as $queryResult) {
                    if ($item->getSku() == $queryResult['product_sku']) {
                        if ((array_search($customerId, explode(",", $queryResult['customer_id'])) !== false || $queryResult['customer_id'] == '*')
                            && (array_search($customerGroup, explode(",", $queryResult['customer_group_id'])) !== false || $queryResult['customer_group_id'] == '*')) {
                            if ($queryResult['apply_to_price'] && $this->canApply($queryResult, $prevApplyToPrice, $qty)) {
                                $tierPriceData = [
                                    'erp_promo_id' => $queryResult['erp_promo_id'],
                                    'discount_percentage' => $queryResult['discount_percentage'],
                                    'discount_amount' => $queryResult['discount_amount'],
                                    'apply_to_price' => $queryResult['apply_to_price']
                                ];
                                $prevApplyToPrice = $queryResult;
                            } elseif (!$queryResult['apply_to_price'] && $this->canApply($queryResult, $prevRow, $qty)) {
                                $tierPriceData = [
                                    'erp_promo_id' => $queryResult['erp_promo_id'],
                                    'discount_percentage' => $queryResult['discount_percentage'],
                                    'discount_amount' => $queryResult['discount_amount'],
                                    'apply_to_price' => $queryResult['apply_to_price']
                                ];
                                $prevRow = $queryResult;
                            }
                        }
                    }
                }

                if (!empty($tierPriceData)) {
                    $item->setTierPriceData(json_encode([$tierPriceData]));
                    $item->setQtyTierPrice($qty);
                } else {
                    $item->setTierPriceData(null);
                    $item->setQtyTierPrice(null);
                }

                // if (!empty($applyToPrice)) {
                //     $item->setOriginalCustomPrice($applyToPrice['discount_amount']);
                // } else {
                //     $item->setOriginalCustomPrice(null);
                // }
            }

            return $discount;
        }

        return 0;
    }

    private function canApply($row, $prevRow, $qty)
    {
        // Tier price can be applied, if:
        // tier qty is lower than product qty
        if ($qty < $row['step_qty']) {
            return false;
        }

        if (!empty($prevRow)) {
            // and tier qty is bigger than previous qty
            if ($row['step_qty'] < $prevRow['step_qty']) {
                return false;
            }

            if ($prevRow['step_qty'] == $row['step_qty']) {
                if ($row['customer_id'] == '*' && $prevRow['customer_id'] != '*') {
                    return false;
                }

                if ($row['customer_id'] != '*' && $prevRow['customer_id'] == '*') {
                    return true;
                }

                if ($row['customer_group_id'] == '*' && $prevRow['customer_group_id'] != '*') {
                    return false;
                }

                if ($row['customer_group_id'] != '*' && $prevRow['customer_group_id'] == '*') {
                    return true;
                }

                if ($row['discount_percentage'] > $prevRow['discount_percentage']) {
                    return false;
                }
                
                if ($row['discount_amount'] > $prevRow['discount_amount']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getTierPrice($tierPriceData, $finalPrice)
    {
        $tmp = 0;
        if ($tierPriceData['discount_percentage'] && ($tierPriceData['discount_percentage'] > 0)) {
            $tmp = ((int) $finalPrice * $tierPriceData['discount_percentage']) / 100;
        } elseif ($tierPriceData['discount_amount']) {
            $tmp = $tierPriceData['discount_amount'];
        }

        return round($tmp);
    }
}
