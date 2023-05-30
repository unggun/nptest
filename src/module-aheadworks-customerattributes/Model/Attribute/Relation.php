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

use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

/**
 * Class Relation
 * @package Aheadworks\CustomerAttributes\Model\Attribute
 */
class Relation extends AbstractExtensibleObject implements AttributeRelationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAttributeId()
    {
        return $this->_get(self::ATTRIBUTE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributeId($attributeId)
    {
        return $this->setData(self::ATTRIBUTE_ID, $attributeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionValue()
    {
        return $this->_get(self::OPTION_VALUE);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptionValue($optionValue)
    {
        return $this->setData(self::OPTION_VALUE, $optionValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentAttributeId()
    {
        return $this->_get(self::DEPENDENT_ATTRIBUTE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setDependentAttributeId($attributeId)
    {
        return $this->setData(self::DEPENDENT_ATTRIBUTE_ID, $attributeId);
    }
}
