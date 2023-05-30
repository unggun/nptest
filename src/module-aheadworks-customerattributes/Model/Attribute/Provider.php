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
namespace Aheadworks\CustomerAttributes\Model\Attribute;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\CollectionFactory;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Class Provider
 * @package Aheadworks\CustomerAttributes\Model\Attribute
 */
class Provider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Retrieve order attributes
     *
     * @param bool $usePrefix
     * @return array
     */
    public function getOrderAttributeCodes($usePrefix = true)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $result = [];

        $collection
            ->addSystemHiddenFilter()
            ->addExcludeHiddenFrontendFilter();

        /** @var Attribute $attribute */
        foreach ($collection as $attribute) {
            if ($attribute->getUsedInOrderView()
                || in_array(UsedInForms::ADMIN_CHECKOUT, $attribute->getUsedInForms())) {
                $result[] = $usePrefix
                    ? AttributeResource::COLUMN_PREFIX . $attribute->getAttributeCode()
                    : $attribute->getAttributeCode();
            }
        }

        return $result;
    }

    /**
     * Retrieve order address attributes
     *
     * @return array
     */
    public function getOrderAddressAttributeCodes()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create(AddressMetadataInterface::ENTITY_TYPE_ADDRESS);
        $result = [];

        $collection
            ->addSystemHiddenFilter()
            ->addExcludeHiddenFrontendFilter();

        /** @var Attribute $attribute */
        foreach ($collection as $attribute) {
            if ($attribute->getIsUserDefined()) {
                $result[] = $attribute->getAttributeCode();
            }
        }

        return $result;
    }

    /**
     * Retrieve order grid attributes
     *
     * @return AttributeInterface[]
     */
    public function getOrderGridAttributes()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $result = [];

        $collection
            ->addSystemHiddenFilter()
            ->addExcludeHiddenFrontendFilter();

        /** @var Attribute $attribute */
        foreach ($collection as $attribute) {
            if ($attribute->getUsedInOrderGrid()) {
                $result[] = $attribute;
            }
        }

        return $result;
    }
}
