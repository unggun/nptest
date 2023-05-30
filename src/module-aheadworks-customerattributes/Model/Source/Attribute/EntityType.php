<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Model\Source\Attribute;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class EntityType
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class EntityType implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' =>  CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                'label' => __('Customer')
            ],
            [
                'value' => AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
                'label' => __('Customer Address')
            ]
        ];
    }
}
