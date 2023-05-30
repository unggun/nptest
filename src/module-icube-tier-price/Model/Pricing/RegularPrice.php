<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Icube\TierPrice\Model\Pricing;

use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Authorization\Model\CompositeUserContext;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zend_Db;

/**
 * Class RegularPrice
 */
class RegularPrice extends \Magento\Catalog\Pricing\Price\RegularPrice
{
    protected $tierPrice;
    protected $resourceConnection;
    protected $userContext;
    protected $customerRepository;
    protected $storeManager;
    protected $timezoneInterface;

    /**
     * @param SaleableInterface $saleableItem
     * @param float $quantity
     * @param CalculatorInterface $calculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        SaleableInterface $saleableItem,
        $quantity,
        CalculatorInterface $calculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        ResourceConnection $resourceConnection,
        CompositeUserContext $userContext,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezoneInterface
    ) {
        parent::__construct(
            $saleableItem,
            $quantity,
            $calculator,
            $priceCurrency
        );
        $this->priceInfo = $saleableItem->getPriceInfo();
        $this->resourceConnection = $resourceConnection;
        $this->userContext = $userContext;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        if ($this->value === null) {
            $price = $this->product->getPrice();
            $priceInCurrentCurrency = $this->priceCurrency->convertAndRound($price);
            $this->value = $priceInCurrentCurrency ? (float)$priceInCurrentCurrency : 0;
        }
        if ($this->userContext->getUserType() == UserContextInterface::USER_TYPE_CUSTOMER) {
            if ($this->tierPrice === null) {
                $product = $this->getProduct();

                $customer = $this->customerRepository->getById($this->userContext->getUserId());
                $customerId = $this->userContext->getUserId();
                $customerGroup = $customer->getGroupId();

                $dateNow = $this->timezoneInterface->date()->format('Y-m-d');
                $connection = $this->resourceConnection->getConnection();
                $tableName = $this->resourceConnection->getTableName('icube_tier_price');
                $query = "SELECT discount_amount, customer_id, customer_group_id FROM " . $tableName . " WHERE apply_to_price = 1 && vendor_code = '". $product->getSellerId() ."' && product_sku = '".$product->getSku()."' && (start_date <= '".$dateNow."' || start_date IS NULL) && (end_date >= '".$dateNow."' || end_date IS NULL);";
                $queryResults = $connection->fetchAll(
                    $query,
                    [],
                    Zend_Db::FETCH_ASSOC
                );

                $prevApplyToPrice = [];
                foreach ($queryResults as $queryResult) {
                    if ((array_search($customerId, explode(",", $queryResult['customer_id'])) !== false || $queryResult['customer_id'] == '*') && (array_search($customerGroup, explode(",", $queryResult['customer_group_id'])) !== false || $queryResult['customer_group_id'] == '*')) {
                        if ($this->canApply($queryResult, $prevApplyToPrice, 1)) {
                            $applyToPrice = [
                                'discount_amount' => $queryResult['discount_amount']
                            ];
                            $prevApplyToPrice = $queryResult;
                        }
                    }
                }

                $tmp = 0;
                if (!empty($applyToPrice)) {
                    // if ($applyToPrice['discount_percentage'] && ($applyToPrice['discount_percentage'] > 0)) {
                    //     $tmp = $this->value - (((int) $this->value * $applyToPrice['discount_percentage']) / 100);
                    // } elseif ($applyToPrice['discount_amount']) {
                    //     $tmp = $this->value - $applyToPrice['discount_amount'];
                    // }
                    $tmp = $applyToPrice['discount_amount'];
                }

                $this->tierPrice = round($tmp);
            }

            return $this->tierPrice > 0 ? $this->tierPrice : $this->value;
        }
        return $this->value;
    }

    private function canApply($row, $prevRow, $qty)
    {
        if (!empty($prevRow)) {
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

            // if ($row['discount_percentage'] > $prevRow['discount_percentage']) {
            //     return false;
            // }
            
            if ($row['discount_amount'] > $prevRow['discount_amount']) {
                return false;
            }
        }

        return true;
    }
}
