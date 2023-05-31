<?php

namespace Icube\TierPrice\Plugin;

use Magento\Framework\Pricing\SaleableInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Icube\TierPrice\Model\TierPriceFactory;

class MinimalTierPriceCalculator
{
    /**
     * Get raw value of "as low as" as a minimal among tier prices{@inheritdoc}
     *
     * @param \Magento\Catalog\Pricing\Price\MinimalTierPriceCalculator $subject
     * @param \Closure $proceed
     * @param SaleableInterface $saleableItem
     *
     * @return float|null
     */

    public function __construct(
        SessionFactory $customerSession,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezoneInterface,
        TierPriceFactory $tierPriceFactory
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->timezoneInterface = $timezoneInterface;
        $this->tierPriceFactory = $tierPriceFactory;
    }
    
    public function aroundGetValue(
        \Magento\Catalog\Pricing\Price\MinimalTierPriceCalculator $subject,
        \Closure $proceed,
        SaleableInterface $saleableItem
    ) {
        $tierResult = [];
        $result = [];
        $productTierPrices = [];
        $productTierPrices = $saleableItem->getTierPrices();
        
        if (!empty($productTierPrices)) {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $storeId = $this->storeManager->getStore()->getId();

            $customer = $this->customerSession->create()->getCustomer();
            $customerId = $customer->getId();
            $custGroupId = $customer->getGroupId();
            $productId = $saleableItem->getId();

            $dateNow = $this->timezoneInterface->date()->format('Y-m-d');
            $tierpriceData = $this->tierPriceFactory->create()->getCollection()
                            ->addFieldToFilter('product_sku', ['eq' => $saleableItem->getSku()])
                            ->addFieldToFilter(['start_date','start_date'], [['lteq' => $dateNow], ['null' => 'this_value_doesnt_matter']])
                            ->addFieldToFilter(['end_date','end_date'], [['gteq' => $dateNow], ['null' => 'this_value_doesnt_matter']]);

            foreach ($tierpriceData as $tierData) {
                $customerGroup = explode(',', $tierData->getCustomerGroupId());
                $isAnyGroup = array_search($custGroupId, $customerGroup);
                $customer = explode(',', $tierData->getCustomerId());
                $isAny = array_search($customerId, $customer);
                $discount = ($tierData->getDiscountPercentage()) ? $tierData->getDiscountPercentage() : $tierData->getDiscountAmount();
                $type = ($tierData->getDiscountPercentage()) ? 'percent' : 'fixed';
                $percentage = ($type == 'percent') ? $tierData->getDiscountPercentage() : null;

                if ($tierData->getCustomerId() == $customerId && $tierData->getCustomerGroupId() == $custGroupId) {
                    $result[] = [
                        "id" => $tierData->getTierDiscountId(),
                        "website_id" => $websiteId,
                        "customer_group_id" => $custGroupId,
                        "qty" => $tierData->getStepQty(),
                        "price_value_type" => $type,
                        'value' => $discount,
                        'percentage' => $percentage,
                        'store_id' => $storeId,
                        'product_id' => $productId,
                        'priority' => 1
                    ];
                } elseif ($tierData->getCustomerId() !== '*' && $isAny !== false) {
                    $result[] = [
                        "id" => $tierData->getTierDiscountId(),
                        "website_id" => $websiteId,
                        "customer_group_id" => $custGroupId,
                        "qty" => $tierData->getStepQty(),
                        "price_value_type" => $type,
                        'value' => $discount,
                        'percentage' => $percentage,
                        'store_id' => $storeId,
                        'product_id' => $productId,
                        'priority' => 2
                    ];
                } elseif ($tierData->getCustomerGroupId() !== '*' && $isAnyGroup !== false) {
                    $groupId = ($tierData->getCustomerGroupId() == '*') ? '32000' : $custGroupId;
                    $result[] = [
                        "id" => $tierData->getTierDiscountId(),
                        "website_id" => $websiteId,
                        "customer_group_id" => $groupId,
                        "qty" => $tierData->getStepQty(),
                        "price_value_type" => $type,
                        'value' => $discount,
                        'percentage' => $percentage,
                        'store_id' => $storeId,
                        'product_id' => $productId,
                        'priority' => 3
                    ];
                } elseif ($tierData->getCustomerGroupId() == '*' && $tierData->getCustomerId() == '*') {
                    $result[] = [
                        "id" => $tierData->getTierDiscountId(),
                        "website_id" => $websiteId,
                        "customer_group_id" => '32000',
                        "qty" => $tierData->getStepQty(),
                        "price_value_type" => $type,
                        'value' => $discount,
                        'percentage' => $percentage,
                        'store_id' => $storeId,
                        'product_id' => $productId,
                        'priority' => 4
                    ];
                }
            }

            array_multisort(array_column($result, 'qty'), SORT_ASC, array_column($result, 'priority'), SORT_ASC, $result);

            $qty = 0;
            foreach($result as $key => $value) {
                if ($value['qty'] !== $qty) {
                    $tierResult[] = $value;
                }
                $qty = $value['qty'];
            }

            if (!empty($tierResult)) {
                foreach ($tierResult as $tier) {
                    if ($tier['price_value_type'] == 'percent') {
                        $pricePercent = $saleableItem->getPrice() - (($saleableItem->getPrice() * $tier['value'])/100);
                        $tierPrices[] = round($pricePercent);
                    } else {
                        $tierPrices[] = $saleableItem->getPrice() - $tier['value'];
                    }
                }

                if (!empty($tierPrices)) {
                    return $tierPrices ? min($tierPrices) : null;
                }
            }
        }
        
        return $proceed($saleableItem);
    }
}