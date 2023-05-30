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
namespace Aheadworks\CustomerAttributes\Model\Attribute\ObjectDataProcessor;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\ObjectData\ProcessorInterface;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Base
 * @package Aheadworks\CustomerAttributes\Model\Attribute\ObjectDataProcessor
 */
class Base implements ProcessorInterface
{
    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param EavConfig $eavConfig
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        EavConfig $eavConfig,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritDoc}
     * @param Attribute $object
     */
    public function afterLoad($object)
    {
        $object->setUsedInForms($object->getUsedInForms());

        return $object;
    }

    /**
     * {@inheritDoc}
     * @param Attribute $object
     * @throws LocalizedException
     */
    public function beforeSave($object)
    {
        $attributeSet = $this->attributeSetFactory->create();
        $entityType = $this->eavConfig->getEntityType($object->getEntityTypeCode());

        if ($object->getId() == null) {
            $inputType = $object->getFrontendInput();
            $backendType = $object->getBackendTypeByInput($inputType);
            $backendModel = $object->getBackendModelByInput($inputType);
            $sourceModel = $object->getSourceModelByInput($inputType);

            $object
                ->setEntityTypeId($entityType->getEntityTypeId())
                ->setAttributeSetId($entityType->getDefaultAttributeSetId())
                ->setBackendType($backendType)
                ->setBackendModel($backendModel)
                ->setSourceModel($sourceModel)
                ->setUsedInForms($this->getUsedInForms($object))
                ->setAttributeGroupId($attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()));
        } else {
            $object
                ->setEntityTypeId($entityType->getEntityTypeId())
                ->setAttributeSetId($entityType->getDefaultAttributeSetId())
                ->setAttributeGroupId($attributeSet->getDefaultGroupId($entityType->getDefaultAttributeSetId()));
        }

        return $object;
    }

    /**
     * Retrieve used in forms
     *
     * @param Attribute $object
     * @return array
     */
    private function getUsedInForms($object)
    {
        $usedInForms = (array)$object->getUsedInForms();

        switch ($object->getEntityTypeCode()) {
            case CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER:
                if (!in_array(UsedInForms::ADMIN_CUSTOMER_ACCOUNT_EDIT, $usedInForms)) {
                    $usedInForms = array_merge($usedInForms, [UsedInForms::ADMIN_CUSTOMER_ACCOUNT_EDIT]);
                }
                break;
            case AddressMetadataInterface::ENTITY_TYPE_ADDRESS:
                if (!in_array(UsedInForms::ADMIN_CUSTOMER_ADDRESS, $usedInForms)) {
                    $usedInForms = array_merge($usedInForms, [UsedInForms::ADMIN_CUSTOMER_ADDRESS]);
                }
                break;
            default:
                break;
        }

        return $usedInForms;
    }
}
