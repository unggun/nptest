<?php

namespace Icube\TierPrice\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\AuthorizationInterface;
use Icube\TierPrice\Helper\TierPriceHelper;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * Resolver to get filtered tier price
 */
class GetTierPrice implements ResolverInterface
{
    /**
     * Constructor
     *
     * @param AuthorizationInterface $authorization
     * @param TierPriceHelper $tierPriceHelper
     */
    public function __construct(
        AuthorizationInterface $authorization,
        TierPriceHelper $tierPriceHelper
    ) {
        $this->authorization = $authorization;
        $this->tierPriceHelper = $tierPriceHelper;
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
        $isAllow = $this->authorization->isAllowed('Icube_TierPrice::tierprice_graphql');
        if (!$isAllow) {
            throw new GraphQlAuthorizationException(__('Token invalid'));
        }

        if (isset($args['filter']['start_date'])) {
            $startDate = $args['filter']['start_date'][0];
            unset($args['filter']['start_date']);
            $args['filter']['start_date']['gt'] = $startDate;
        }
        if (isset($args['filter']['end_date'])) {
            $endDate = $args['filter']['end_date'][0];
            unset($args['filter']['end_date']);
            $args['filter']['end_date']['lt'] = $endDate;
        }
        if (isset($args['filter']['product_sku'])) {
            $missingSku = $this->tierPriceHelper->checkSkuExist($args['filter']['product_sku']);
            if (count($missingSku) > 0) {
                throw new GraphQlNoSuchEntityException(__("SKU not found: %1", implode(',', $missingSku)));
            }
        }
        $dataReturn = $this->tierPriceHelper->getFilteredTierPrice($args);
        return $dataReturn;
    }
}
