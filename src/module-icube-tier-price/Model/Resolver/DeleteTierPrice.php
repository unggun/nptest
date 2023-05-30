<?php

namespace Icube\TierPrice\Model\Resolver;

use Icube\TierPrice\Model\TierPriceFactory;
use Icube\TierPrice\Model\TierPriceRepository;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver to delete tier price
 */
class DeleteTierPrice implements ResolverInterface
{
    /**
     * Constructor
     *
     * @param AuthorizationInterface $authorization
     * @param TierRepository $tierPriceRepository
     * @param TierPriceFactory $tierPriceFactory
     */
    public function __construct(
        AuthorizationInterface $authorization,
        TierPriceRepository $tierPriceRepository,
        TierPriceFactory $tierPriceFactory
    ) {
        $this->authorization = $authorization;
        $this->tierPriceRepository = $tierPriceRepository;
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
        $isAllow = $this->authorization->isAllowed('Icube_TierPrice::tierprice_graphql');
        if (!$isAllow) {
            throw new GraphQlAuthorizationException(__('Token invalid'));
        }

        $tierPrice = $this->tierPriceFactory->create()->getCollection()
                        ->addFieldToFilter('tier_discount_id', $args['tier_discount_id'])
                        ->getFirstItem();

        if ($this->tierPriceRepository->delete($tierPrice)) {
            $dataReturn['status'] = true;
            $dataReturn['message'] = "Record deleted";
            return $dataReturn;
        }
        throw new GraphQlNoSuchEntityException(__("No matching record found. tier_discount_id = %1", $args['tier_discount_id']));
    }
}
