<?php
namespace Icube\TierPrice\Model\Api;

use Icube\TierPrice\Api\TierPriceInterface;
use Icube\TierPrice\Model\TierPriceFactory;
use Icube\TierPrice\Model\Indexer\Processor;
use Icube\TierPrice\Helper\Data as DataHelper;
use Icube\TierPrice\Helper\TierPriceHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class TierPrice implements TierPriceInterface
{
    public function __construct(
        TierPriceFactory $tierPriceFactory,
        DataHelper $dataHelper,
        TierPriceHelper $tierPriceHelper,
        Processor $indexerProcessor,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storemanager,
        CustomerGroup $customerGroup,
        TimezoneInterface $timezoneInterface
    ) {
        $this->tierPriceFactory = $tierPriceFactory;
        $this->dataHelper = $dataHelper;
        $this->tierPriceHelper = $tierPriceHelper;
        $this->indexerProcessor = $indexerProcessor;
        $this->customerFactory = $customerFactory;
        $this->storemanager = $storemanager;
        $this->customerGroup = $customerGroup;
        $this->timezoneInterface = $timezoneInterface;
    }

    public function setData($data)
    {
        if (!isset($data['tier_discount_id']) && (!isset($data['vendor_code']) || !isset($data['sku']) || !isset($data['step_qty']) || !isset($data['customer_email']) || !isset($data['customer_group']))) {
            $response = ['status' => false, 'message' => 'vendor_code, sku, step_qty, customer_email, customer_group is mandatory if tier_discount_id is not set'];
            return $this->formatResultApi($response);
        }

        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        if (isset($data['start_date']) && $data['start_date'] < $currentDate) {
            $response = ['status' => false, 'message' => "Start date can't be lower than current date"];
            return $this->formatResultApi($response);
        }

        if (isset($data['end_date'])) {
            if (isset($data['start_date'])) {
                if ($data['end_date'] < $data['start_date']) {
                    $response = ['status' => false, 'message' => "End date can't be lower than start_date"];
                    return $this->formatResultApi($response);
                }
            }

            if ($data['end_date'] < $currentDate) {
                $response = ['status' => false, 'message' => "End date can't be lower than current date"];
                return $this->formatResultApi($response);
            }
        }

        if (isset($data['apply_to_price'])) {
            if (!is_bool($data['apply_to_price'])) {
                $response = ['status' => false, 'message' => "Apply To Price must be boolean"];
                return $this->formatResultApi($response);
            }
        }

        try {
            $websiteId = $this->storemanager->getStore()->getWebsiteId();
            $customerGroupId = $data['customer_group'];
            if (isset($data['customer_group']) && $data['customer_group'] !== '*') {
                $custGroupList = explode(',', $data['customer_group']);

                if (count($custGroupList) > 1) {
                    $response = ['status' => false, 'message' => 'please set customer group with * or spesific group'];
                    return $this->formatResultApi($response);
                }

                $customerGroupData = $this->customerGroup->load($data['customer_group'], 'customer_group_code');

                if (!$customerGroupData->getId()) {
                    $response = ['status' => false, 'message' => 'customer group '.$data['customer_group'].' not found'];
                    return $this->formatResultApi($response);
                }

                $customerGroupId = $customerGroupData->getId();
            }

            /*update tier price*/
            if (isset($data['tier_discount_id'])) {
                $tierprice = $this->tierPriceFactory->create();
                $tierprice->load($data['tier_discount_id'], 'tier_discount_id');

                if (!$tierprice->getTierDiscountId()) {
                    $response = ['status' => false, 'message' => 'tier_discount_id not found'];
                    return $this->formatResultApi($response);
                }

                $customerId = $data['customer_email'];
                if (isset($data['customer_email']) && $data['customer_email'] !== '*') {
                    $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($data['customer_email']);

                    if (!$customer->getId()) {
                        $response = ['status' => false, 'message' => 'customer_email'.$data['customer_email'].' not found'];
                        return $this->formatResultApi($response);
                    }

                    $customerId = $customer->getId();
                }

                $this->updateTierPrice($data['tier_discount_id'], $data, $tierprice, $customerGroupId, $customerId);

                $response = ['status' => true, 'message' => 'data updated successfully'];
            } else {
                /*insert tier price*/
                if ($data['customer_email'] !== '*') {
                    $customerEmails = explode(',', $data['customer_email']);
                    foreach ($customerEmails as $custEmail) {
                        $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($custEmail);
                        if (!$customer->getId()) {
                            $response = ['status' => false, 'message' => 'customer_email'.$custEmail.' not found'];
                            return $this->formatResultApi($response);
                        }
                        $customerIds[] = $customer->getEntityId();
                    }
                } else {
                    $customerIds[] = $data['customer_email'];
                }

                $qty = (isset($data['apply_to_price']) && $data['apply_to_price'] == 1) ? 1 : $data['step_qty'];
                foreach ($customerIds as $customerId) {
                    $sku = $data['vendor_code'].'-'.$data['sku'];
                    $tierPrice = $this->tierPriceHelper->getTierPrice($sku, $data['vendor_code'], $customerGroupId, $qty, $customerId);
                    if ($tierPrice->getTierDiscountId()) {
                        $this->updateTierPrice($tierPrice->getTierDiscountId(), $data, $tierPrice, $customerGroupId, $customerId);
                        $response = ['status' => true, 'message' => 'data updated successfully'];
                    } else {
                        $this->setTierPrice($data, $customerGroupId, $customerId);
                        $response = ['status' => true, 'message' => 'data saved successfully'];
                    }
                }
            }

            $this->indexerProcessor->markIndexerAsInvalid();
        } catch (\Exception $e) {
            $response = ['status' => false, 'message' => $e->getMessage()];
        }

        return $this->formatResultApi($response);
    }

    public function get($vendor_code, $sku)
    {
        try {
            $tierData = [];
            $product_sku = $vendor_code.'-'.$sku;
            $websiteId = $this->storemanager->getStore()->getWebsiteId();
            $tierpriceData = $this->dataHelper->getTierPrice($vendor_code, $product_sku);

            if ($tierpriceData->getData()) {
                foreach ($tierpriceData as $tierPrice) {
                    $customerGroups = [];
                    if ($tierPrice->getCustomerGroupId() !== '*') {
                        $groupIds = explode(',', $tierPrice->getCustomerGroupId());
                        foreach ($groupIds as $groupId) {
                            $customerGroupData = $this->customerGroup->load($groupId, 'customer_group_id');
                            $customerGroups[] = $customerGroupData->getCode();
                        }
                    }

                    $customerEmail = [];
                    if ($tierPrice->getCustomerId()) {
                        $ids = explode(',', $tierPrice->getCustomerId());
                        foreach ($ids as $id) {
                            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->load($id);
                            $customerEmail[] = $customer->getEmail();
                        }
                    }

                    $tierData[] = [
                        'tier_discount_id' => $tierPrice->getTierDiscountId(),
                        'erp_promo_id' => $tierPrice->getErpPromoId(),
                        'erp_id' => $tierPrice->getErpId(),
                        'vendor_code' => $tierPrice->getVendorCode(),
                        'creator' => $tierPrice->getCreator(),
                        'customer_group' => ($tierPrice->getCustomerGroupId() == '*') ? '*' : implode(',', $customerGroups),
                        'customer_group_id' => $tierPrice->getCustomerGroupId(),
                        'customer_email' => ($tierPrice->getCustomerId()) ? implode(',', $customerEmail) : null,
                        'customer_id' => $tierPrice->getCustomerId(),
                        'sku' => $sku,
                        'step_qty' => $tierPrice->getStepQty(),
                        'discount_percentage' => $tierPrice->getDiscountPercentage(),
                        'discount_amount' => $tierPrice->getDiscountAmount(),
                        'start_date' => $tierPrice->getStartDate(),
                        'end_date' => $tierPrice->getEndDate(),
                        'apply_to_price' => $tierPrice->getApplyToPrice() == 1 ? true : false
                    ];
                }
            }

            $status = (!$tierData) ? false : true;
            $message = (!$tierData) ? 'data not found' : 'get data successfully';
            $response = ['status' => $status, 'message' => $message, 'data' => $tierData];
        } catch (Exception $e) {
            $response = ['status' => false, 'message' => $e->getMessage(), 'data' => null];
        }
        return $this->formatResultApi($response);
    }

    public function delete($tier_discount_id)
    {
        try {
            $ids = explode(',', $tier_discount_id);
            foreach ($ids as $id) {
                $tierprice = $this->tierPriceFactory->create()->load($id, 'tier_discount_id');

                if (!$tierprice->getTierDiscountId()) {
                    $response = ['status' => false, 'message' => 'data with tier_discount_id '.$id.' not found'];
                    return $this->formatResultApi($response);
                }

                $tierprice->delete();

                $response = ['status' => true, 'message' => 'data deleted successfully'];
                $this->indexerProcessor->markIndexerAsInvalid();
            }
        } catch (\Exception $e) {
            $response = ['status' => false, 'message' => $e->getMessage()];
        }

        return $this->formatResultApi($response);
    }

    private function formatResultApi($response)
    {
        return [
          'result' => $response
        ];
    }

    private function updateTierPrice($tierId, $data, $tierprice, $customerGroupId, $customerId)
    {
        $tierprice = $this->tierPriceFactory->create();
        $tierprice->load($tierId, 'tier_discount_id');
        $tierprice->setErpPromoId((isset($data['erp_promo_id'])) ? $data['erp_promo_id'] : $tierprice->getErpPromoId());
        $tierprice->setErpId((isset($data['erp_id'])) ? $data['erp_id'] : $tierprice->getErpId());
        $tierprice->setVendorCode((isset($data['vendor_code'])) ? $data['vendor_code'] : $tierprice->getVendorCode());
        $tierprice->setCreator((isset($data['creator'])) ? $data['creator'] : $tierprice->getCreator());
        $tierprice->setCustomerGroupId(($customerGroupId) ? $customerGroupId : $tierprice->getCustomerGroupId());
        $tierprice->setCustomerId(($customerId) ? $customerId : $tierprice->getCustomerId());
        $tierprice->setProductSku((isset($data['sku'])) ? $data['vendor_code'].'-'.$data['sku'] : $tierprice->getProductSku());

        if (isset($data['apply_to_price']) && $data['apply_to_price'] == true) {
            $qty = 1;
        } elseif (isset($data['step_qty'])) {
            $qty = $data['step_qty'];
        } else {
            $qty = $tierprice->getStepQty();
        }

        $tierprice->setStepQty($qty);
        $tierprice->setDiscountPercentage((isset($data['discount_percentage'])) ? $data['discount_percentage'] : $tierprice->getDiscountPercentage());
        $tierprice->setDiscountAmount((isset($data['discount_amount'])) ? $data['discount_amount'] : $tierprice->getDiscountAmount());
        $tierprice->setStartDate((isset($data['start_date'])) ? $data['start_date'] : $tierprice->getStartDate());
        $tierprice->setEndDate((isset($data['end_date'])) ? $data['end_date'] : $tierprice->getEndDate());
        $tierprice->setApplyToPrice((isset($data['apply_to_price'])) ? $data['apply_to_price'] : $tierprice->setApplyToPrice());
        $tierprice->save();
    }

    private function setTierPrice($data, $customerGroupId, $customerId)
    {
        $qty = (isset($data['apply_to_price']) && $data['apply_to_price'] == true) ? 1 : $data['step_qty'];
        $tierData = [
            'erp_promo_id' => (isset($data['erp_promo_id'])) ? $data['erp_promo_id'] : null,
            'erp_id' => (isset($data['erp_id'])) ? $data['erp_id'] : null,
            'vendor_code' => $data['vendor_code'],
            'creator' => (isset($data['creator'])) ? $data['creator'] : 'seller',
            'customer_group_id' => $customerGroupId,
            'customer_id' => $customerId,
            'product_sku' => $data['vendor_code'].'-'.$data['sku'],
            'step_qty' => $qty,
            'discount_percentage' => (isset($data['discount_percentage'])) ? $data['discount_percentage'] : null,
            'discount_amount' => (isset($data['discount_amount'])) ? $data['discount_amount'] : null,
            'start_date' => (isset($data['start_date'])) ? $data['start_date'] : null,
            'end_date' => (isset($data['end_date'])) ? $data['end_date'] : null,
            'apply_to_price' => (isset($data['apply_to_price'])) ? $data['apply_to_price'] : false
        ];

        $tierprice = $this->tierPriceFactory->create();
        $tierprice->setData($tierData)->save();
    }
}
