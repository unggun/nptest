<?php

namespace Icube\TierPrice\Model\Resolver;

use Icube\TierPrice\Helper\TierPriceHelper;
use Icube\TierPrice\Model\TierPriceFactory;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Resolver to insert tier price
 */
class InsertTierPrice implements ResolverInterface
{
    protected TimezoneInterface $timezoneInterface;
    protected TierPriceHelper $tierPriceHelper;
    protected AuthorizationInterface $authorization;
    protected TierPriceFactory $tierPriceFactory;

    /**
     * @param TimezoneInterface $timezoneInterface
     * @param TierPriceHelper $tierPriceHelper
     * @param AuthorizationInterface $authorization
     * @param TierPriceFactory $tierPriceFactory
     */
    public function __construct(
        TimezoneInterface $timezoneInterface,
        TierPriceHelper $tierPriceHelper,
        AuthorizationInterface $authorization,
        TierPriceFactory $tierPriceFactory
    ) {
        $this->timezoneInterface = $timezoneInterface;
        $this->tierPriceHelper = $tierPriceHelper;
        $this->authorization = $authorization;
        $this->tierPriceFactory = $tierPriceFactory;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $customerIds = [];
        $tierPrice = null;
        $dataReturn['message'] = "";

        $isAllow = $this->authorization->isAllowed('Icube_TierPrice::tierprice_graphql');
        if (!$isAllow) {
            throw new GraphQlAuthorizationException(__('Token invalid'));
        }

        if (isset($args['input']['start_date'])) {
            $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
            if ($args['input']['start_date'] < $currentDate) {
                throw new GraphQlInputException(__("Start date can't be lower than current date"));
            }
        }

        if (isset($args['input']['end_date'])) {
            $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
            if (isset($args['input']['start_date'])) {
                if ($args['input']['end_date'] < $args['input']['start_date']) {
                    throw new GraphQlInputException(__("End date can't be lower than or equal to start_date"));
                }
            }
            if ($args['input']['end_date'] < $currentDate) {
                throw new GraphQlInputException(__("End date can't be lower than current date if start date is not set"));
            }
        }

        if (isset($args['input']['product_sku'])) {
            $missingSku = $this->tierPriceHelper->checkSkuExist([$args['input']['product_sku']]);
            if (count($missingSku) > 0) {
                throw new GraphQlNoSuchEntityException(__("SKU not found: %1", implode(',', $missingSku)));
            }
        }

        if (isset($args['input']['tier_discount_id'])) {
            $tierPrice = $this->tierPriceHelper->getTierPriceById($args['input']['tier_discount_id']);
            if (!$tierPrice->getTierDiscountId()) {
                throw new GraphQlNoSuchEntityException(__("Tier discount id %1 not found", $args['input']['tier_discount_id']));
            }
            if (isset($args['input']['customer_group_code'])) {
                $customerGroupId = $args['input']['customer_group_code']==="*"
                    ? ["*"]
                    : $this->tierPriceHelper->getCustomerGroupIdByCode([$args['input']['customer_group_code']]);
                if (count($customerGroupId) < 1) {
                    throw new GraphQlNoSuchEntityException(__("Customer group code %1 not found", $args['input']['customer_group_code']));
                }
                $args['input']['customer_group_id'] = $customerGroupId[0];
            }
            $this->updateTierPrice($tierPrice, $args);
            $dataReturn['status'] = true;
            $dataReturn['message'] .= "Update tier price tier_discount_id " . $args['input']['tier_discount_id'] . " success";
            return $dataReturn;
        } else {
            if (!isset($args['input']['vendor_code'])
                || !isset($args['input']['product_sku'])
                || !isset($args['input']['step_qty'])
                || !isset($args['input']['email'])
                || !isset($args['input']['customer_group_code'])
            ) {
                throw new GraphQlInputException(__("vendor_code, product_sku, step_qty, email, and customer_group_code is mandatory if tier_discount_id is not set"));
            }

            if ($args['input']['email'] !== "*") {
                $customerEmails = explode(",", $args['input']['email']);
                foreach ($customerEmails as $email) {
                    $customer = $this->tierPriceHelper->getCustomerByEmail($email);
                    if (!$customer->getEntityId()) {
                        throw new GraphQlNoSuchEntityException(__("Email %1 not found", $email));
                        break;
                    }
                    $customerIds[] = $customer->getEntityId();
                }
            } else {
                $customerIds[] = $args['input']['email'];
            }

            //get customer group id
            $customerGroupId = $args['input']['customer_group_code']==="*"
                ? ["*"]
                : $this->tierPriceHelper->getCustomerGroupIdByCode(explode(',', $args['input']['customer_group_code']));
            if (count($customerGroupId) < 1) {
                throw new GraphQlNoSuchEntityException(__("Customer group code %1 not found", $args['input']['customer_group_code']));
            }
            foreach ($customerGroupId as $cgId) {
                $args['input']['customer_group_id'] = $cgId;
                $qty = (isset($args['input']['apply_to_price']) && $args['input']['apply_to_price'] == 1)
                    ? 1
                    : $args['input']['step_qty'];
                $applyToPrice = empty($args['input']['apply_to_price']) ? 0 : 1;
                //insert/update each customer id
                foreach ($customerIds as $custId) {
                    $tierPrice = $this->tierPriceHelper->getTierPrice(
                        $args['input']['product_sku'],
                        $args['input']['vendor_code'],
                        $args['input']['customer_group_id'],
                        $qty,
                        $custId,
                        $applyToPrice
                    );
                    if ($tierPrice->getTierDiscountId()) {
                        //update
                        $this->updateTierPrice($tierPrice, $args);
                        $dataReturn['status'] = true;
                        $dataReturn['message'] .= "Update tier price customer id $custId success, ";
                    } else {
                        //insert
                        $args['input']['customer_id'] = $custId;
                        $this->insertTierPrice($args);
                        $dataReturn['status'] = true;
                        $dataReturn['message'] .= "Insert tier price customer id $custId success, ";
                    }
                }
            }
            return $dataReturn;
        }
    }

    private function insertTierPrice($args)
    {
        $qty = (isset($args['input']['apply_to_price']) && $args['input']['apply_to_price'] == 1) ? 1 : $args['input']['step_qty'];
        $tierPrice = $this->tierPriceFactory->create();
        $tierPrice->setErpPromoId($args['input']['erp_promo_id'] ?? null);
        $tierPrice->setErpId($args['input']['erp_id'] ?? null);
        $tierPrice->setVendorCode($args['input']['vendor_code'] ?? null);
        $tierPrice->setCreator($args['input']['creator'] ?? null);
        $tierPrice->setCustomerGroupId($args['input']['customer_group_id'] ?? '*');
        $tierPrice->setCustomerId($args['input']['customer_id'] ?? $args['input']['email']);
        $tierPrice->setProductSku($args['input']['product_sku'] ?? null);
        $tierPrice->setStepQty($qty ?? null);
        $tierPrice->setDiscountPercentage($args['input']['discount_percentage'] ?? null);
        $tierPrice->setDiscountAmount($args['input']['discount_amount'] ?? null);
        $tierPrice->setStartDate($args['input']['start_date'] ?? null);
        $tierPrice->setEndDate($args['input']['end_date'] ?? null);
        $tierPrice->setApplyToPrice($args['input']['apply_to_price'] ?? false);
        $tierPrice->save();
    }

    private function updateTierPrice($tierPrice, $args)
    {
        $tierPrice->setErpPromoId($args['input']['erp_promo_id'] ?? $tierPrice->getErpPromoId());
        $tierPrice->setErpId($args['input']['erp_id'] ?? $tierPrice->getErpId());
        $tierPrice->setCreator($args['input']['creator'] ?? $tierPrice->getCreator());
        $tierPrice->setVendorCode($args['input']['vendor_code'] ?? $tierPrice->getVendorCode());
        $tierPrice->setCustomerGroupId($args['input']['customer_group_id'] ?? $tierPrice->getCustomerGroupId());
        $tierPrice->setProductSku(($args['input']['product_sku']) ?? $tierPrice->getProductSku());
        $tierPrice->setStepQty($args['input']['step_qty'] ?? $tierPrice->getStepQty());
        $tierPrice->setDiscountPercentage($args['input']['discount_percentage'] ?? $tierPrice->getDiscountPercentage());
        $tierPrice->setDiscountAmount($args['input']['discount_amount'] ?? $tierPrice->getDiscountAmount());
        $tierPrice->setStartDate($args['input']['start_date'] ?? $tierPrice->getStartDate());
        $tierPrice->setEndDate($args['input']['end_date'] ?? $tierPrice->getEndDate());
        $tierPrice->setTierPriceId($tierPrice->getTierDiscountId());
        $tierPrice->setApplyToPrice($args['input']['apply_to_price'] ?? $tierPrice->getApplyToPrice());
        if (isset($args['input']['apply_to_price']) == 1) {
            $tierPrice->setStepQty(1);
        }
        $tierPrice->save();
    }
}
