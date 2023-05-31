<?php

namespace Icube\MisiPintar\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class GetMissionList implements ResolverInterface
{
    public function __construct(
        DataProvider\MissionList $missionListDataProvider
    ) {
        $this->missionListDataProvider = $missionListDataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current audience is not eligible for this mission.'));
        }
        $missionListData = $this->missionListDataProvider->getMissionList($context);
        if (!isset($missionListData['missions'])) {
            throw new GraphQlNoSuchEntityException(__('There is no mission for current audience.'));
        }
        return $missionListData;
    }
}
