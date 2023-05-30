<?php

namespace Icube\TierPrice\Helper;

use Icube\TierPrice\Model\TierPriceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        Context $context,
        GroupRepositoryInterface $groupRepository,
        TimezoneInterface $timezoneInterface,
        SenderResolverInterface $senderResolver,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        LoggerInterface $loggerInterface,
        PriceHelper $priceHelper,
        TierPriceFactory $tierPriceFactory,
        SessionFactory $customerSession,
        CalculatorInterface $calculator
    ) {
        $this->groupRepository = $groupRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->senderResolver = $senderResolver;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->loggerInterface = $loggerInterface;
        $this->priceHelper = $priceHelper;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->customerSession = $customerSession;
        $this->calculator = $calculator;
        parent::__construct($context);
    }

    public function getTierPrice($vendorCode, $sku)
    {
        $tierpriceData = $this->tierPriceFactory->create()->getCollection()
              ->addFieldToFilter('vendor_code', $vendorCode)
              ->addFieldToFilter('product_sku', $sku);
              
        return $tierpriceData;
    }

    public function getTierPriceWithCustGroup($vendor_code, $sku, $groupId)
    {
        $tierpriceData = $this->getTierPrice($vendor_code, $sku);
        $tierpriceData->addFieldToFilter('customer_group_id', $groupId);
              
        return $tierpriceData->getFirstItem();
    }

    public function getTierPriceDetailsByProduct($products)
    {
        $finalTierPrice = [];
        $result = [];
        $tierResult = [];

        //custom tier prices array
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $storeId = $this->storeManager->getStore()->getId();

        $customer = $this->customerSession->create()->getCustomer();
        $customerId = $customer->getId();
        $custGroupId = $customer->getGroupId();
        $productId = $products->getId();

        $dateNow = $this->timezoneInterface->date()->format('Y-m-d');
        $tierpriceData = $this->tierPriceFactory->create()->getCollection()
                        ->addFieldToFilter('product_sku', ['eq' => $products->getSku()])
                        ->addFieldToFilter('apply_to_price', ['eq' => 0])
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
            } elseif ($tierData->getCustomerGroupId() == '*' && $isAny !== false) {
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
                    'priority' => 2
                ];
            } elseif ($tierData->getCustomerId() == '*' && $isAnyGroup !== false) {
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
        foreach ($result as $key => $value) {
            if ($value['qty'] !== $qty) {
                $tierResult[] = $value;
            }
            $qty = $value['qty'];
        }

        if (!empty($tierResult)) {
            foreach ($tierResult as $tier) {
                $allGroup = ($tier["customer_group_id"] != "32000")? "0" : "1";
                $each = [];
                $each["price_id"]   = $tier['id'] ?? 0;
                $each["website_id"] = $tier["website_id"] ?? "0";
                $each["all_groups"] = $allGroup ?? "0";
                $each["cust_group"] = $tier["customer_group_id"] ?? "0";
                $each["price_qty"]  = $tier["qty"] ?? "0";
                $each["price_value_type"] = $tier['price_value_type'];
                $each["product_id"] = $tier['product_id'];
                $each["price"] = $this->calculator->getAmount($tier["value"] ?? "0", $products);
                $each["website_price"] = $tier["value"] ?? "0";
                
                $finalTierPrice[] = $each;
            }
        }

        return $finalTierPrice;
    }

    public function getCustomerGroupCode($groupId)
    {
        return $this->groupRepository->getById($groupId);
    }

    public function getTierDiscountItem($tierDatas, $price, $qty)
    {
        $discount = 0;
        if ($tierDatas) {
            $tmp = 0;
            foreach (json_decode($tierDatas, true) as $tierPrice) {
                if (isset($tierPrice['discount_percentage']) && ($tierPrice['discount_percentage'] > 0)) {
                    $tmp = ((int) $price * $tierPrice['discount_percentage']) / 100;
                } elseif (isset($tierPrice['discount_amount'])) {
                    $tmp = $tierPrice['discount_amount'];
                } else {
                    $tmp = 0;
                }
            }
            $discount += $tmp * $qty;
            $discount = round($discount);
        }

        return $discount;
    }
}
