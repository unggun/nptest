<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\TierPrice\Helper;

use Icube\TierPrice\Model\TierPriceFactory;
use Icube\TierPrice\Model\TierPriceRepository;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\GroupFactory as CustomerGroup;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupFactory;
use Magento\Framework\Api\FilterBuilder as Filter;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;

class TierPriceHelper extends AbstractHelper
{
    public function __construct(
        Filter $filter,
        CustomerGroup $customerGroup,
        CustomerGroupFactory $customerGroupFactory,
        CustomerFactory $customerFactory,
        TierPriceFactory $tierPriceFactory,
        TierPriceRepository $tierPriceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchCriteria $searchCriteria,
        ProductCollection $productCollection
    ) {
        $this->filter = $filter;
        $this->customerGroup = $customerGroup;
        $this->customerGroupFactory = $customerGroupFactory;
        $this->customerFactory = $customerFactory;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->tierPriceRepository = $tierPriceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchCriteria = $searchCriteria;
        $this->productCollection = $productCollection;
    }

    public function checkSkuExist(array $skus)
    {
        $collection = $this->productCollection->addAttributeToFilter('sku', ['in' => $skus]);
        $existingIds = $collection->getColumnValues('sku');
        return array_diff($skus, $existingIds);
    }

    public function deleteTierPrice($tierDiscountId)
    {
        $dataReturn['status'] = false;
        $dataReturn['message'] = "Failed to delete record id $tierDiscountId";
        if ($this->tierPriceRepository->deleteById($tierDiscountId)) {
            $dataReturn['status'] = true;
            $dataReturn['message'] = "Record id $tierDiscountId deleted";
        }
        return $dataReturn;
    }

    public function getFilteredTierPrice($args)
    {
        $tierPriceData = [];
        $dataReturn['status'] = false;
        $dataReturn['message'] = '';

        $currentPage = isset($args['currentPage']) ? (int)$args['currentPage'] : 1;
        $pageSize = isset($args['pageSize']) ? (int)$args['pageSize'] : 20;

        if ($currentPage < 1) {
            $dataReturn['message'] = "currentPage value must be greater than 0";
            return $dataReturn;
        }
        if ($pageSize < 1) {
            $dataReturn['message'] = "pageSize value must be greater than 0";
            return $dataReturn;
        }
        if (isset($args['filter']['email'])) {
            $args['filter']['customer_id'] = $this->getCustomerIdByEmail($args['filter']['email']);
        }
        if (isset($args['filter']['customer_group_code'])) {
            $args['filter']['customer_group_id'] = $this->getCustomerGroupIdByCode($args['filter']['customer_group_code']);
        }
        unset($args['filter']['customer_group_code']);
        unset($args['filter']['email']);

        $this->searchCriteriaBuilder($args);
        $tierPriceResult = $this->tierPriceRepository->getList($this->searchCriteria);
        $totalCount = (int) $tierPriceResult->getTotalCount();
        $totalPages = ceil($totalCount / $pageSize);

        if ($totalCount == 0) {
            $dataReturn['message'] = "No record matched given criteria";
            return $dataReturn;
        }

        foreach ($tierPriceResult->getItems() as $tierPrice) {
            $tierPriceData[] = $tierPrice->getData();
        }
        $dataReturn['status'] = true;
        $dataReturn['data'] = $tierPriceData;
        $dataReturn['page_info'] = [
            'page_size' => $pageSize,
            'current_page' => $this->searchCriteria->getCurrentPage(),
            'total_pages' => $totalPages
        ];
        $dataReturn['total_count'] = $totalCount;
        return $dataReturn;
    }

    public function getCustomerByEmail($email)
    {
        $customer = $this->customerFactory->create()->getCollection()
                        ->addFieldToFilter('email', $email)
                        ->getFirstItem();
        return $customer;
    }

    public function getDataCustomerGroupId($customerGroups)
    {
        if ($customerGroups !== '*') {
            $custGroupList = explode(',', $customerGroups);

            $customerGroupIds = [];
            foreach ($custGroupList as $custGroup) {
                $customerGroupData = $this->customerGroup->create()->load($custGroup, 'customer_group_code');
                if (!$customerGroupData->getId()) {
                    $response = ['status' => false, 'message' => 'customer group ' . $custGroup . ' not found'];
                    return $response;
                }
                $customerGroupIds[] = $customerGroupData->getId();
            }
            sort($customerGroupIds, 1);
            $customerGroupId = implode(',', $customerGroupIds);
        } else {
            $customerGroupId = $customerGroups;
        }
        return $customerGroupId;
    }

    public function getTierPrice($sku, $vendorCode, $customerGroupId, $stepQty, $customerId, $applyToPrice)
    {
        $tierpriceData = $this->tierPriceFactory->create()->getCollection()
            ->addFieldToFilter('vendor_code', $vendorCode)
            ->addFieldToFilter('product_sku', $sku)
            ->addFieldToFilter('customer_group_id', $customerGroupId)
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('apply_to_price', $applyToPrice)
            // ->addFieldToFilter(['customer_id', 'customer_id'],[
            //     ['eq' => $customerId],
            //     ['eq' => '*']
            // ])
            ->addFieldToFilter('step_qty', $stepQty)
            ->getFirstItem();
        return $tierpriceData;
    }

    public function getTierPriceById($tierDiscountId)
    {
        $tierpriceData = $this->tierPriceFactory->create()->getCollection()
              ->addFieldToFilter('tier_discount_id', $tierDiscountId)
              ->getFirstItem();
        return $tierpriceData;
    }

    public function getCustomerIdByEmail(array $custEmail)
    {
        $customerIds = [];
        foreach ($custEmail as $email) {
            $customer = $this->customerFactory->create()->getCollection()
                        ->addFieldToFilter('email', $email)
                        ->getFirstItem();
            if ($customer->getEntityId()) {
                $customerIds[] = $customer->getEntityId();
            }
            if ($email === "*") {
                $customerIds[] = $email;
            }
        }
        return $customerIds;
    }

    public function getCustomerGroupIdByCode(array $custGroupCode)
    {
        $customerGroupIds = [];
        foreach ($custGroupCode as $groupCode) {
            $customerGroup = $this->customerGroup->create()->getCollection()
                        ->addFieldToFilter('customer_group_code', $groupCode)
                        ->getFirstItem();
            if ($customerGroup->getCustomerGroupId() !== null) {
                $customerGroupIds[] = $customerGroup->getCustomerGroupId();
            }
        }
        return $customerGroupIds;
    }

    private function searchCriteriaBuilder(array $arguments): SearchCriteria
    {
        $_searchCriteriaBuilder = $this->searchCriteriaBuilder;
        if (!empty($arguments['filter'])) {
            foreach ($arguments['filter'] as $key => $filter) {
                $filters = [];
                foreach ($filter as $condition => $value) {
                    $filters[] = $this->filter->setField($key)->setValue($value)->setConditionType($condition)->create();
                }
                // $filters[] = $this->filter->setField($key)->setValue("*")->setConditionType("finset")->create();
                $_searchCriteriaBuilder->addFilters($filters);
            }
        }
        $this->searchCriteria = $_searchCriteriaBuilder->create()
            ->setPageSize($arguments['pageSize'])
            ->setCurrentPage($arguments['currentPage']);
        return $this->searchCriteria;
    }

    public function getTierPriceUploadCSV($customerId, $vendorCode, $customerGroupId, $productSku, $stepQty, $applyToPrice)
    {
        $tierpriceData = $this->tierPriceFactory->create()->getCollection()
              ->addFieldToFilter('customer_id', $customerId)
              ->addFieldToFilter('vendor_code', $vendorCode)
              ->addFieldToFilter('customer_group_id', $customerGroupId)
              ->addFieldToFilter('product_sku', $productSku)
              ->addFieldToFilter('step_qty', $stepQty)
              ->addFieldToFilter('apply_to_price', $applyToPrice)
              ->getFirstItem();

        return $tierpriceData;
    }
}
