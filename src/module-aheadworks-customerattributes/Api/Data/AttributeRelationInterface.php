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
namespace Aheadworks\CustomerAttributes\Api\Data;

/**
 * Interface AttributeRelationInterface
 * @package Aheadworks\CustomerAttributes\Api\Data
 */
interface AttributeRelationInterface
{
    /**#@+
     * Constants defined for keys of the data array.
     * Identical to the name of the getter in snake case
     */
    const ATTRIBUTE_ID = 'attribute_id';
    const OPTION_VALUE = 'option_value';
    const DEPENDENT_ATTRIBUTE_ID = 'dependent_attribute_id';
    /**#@-*/

    /**#@+
     * Additional constants
     */
    const ATTRIBUTE_CODE = 'attribute_code';
    const DEPENDENT_ATTRIBUTE_CODE = 'dependent_attribute_code';
    /**#@-*/

    /**
     * Get attribute ID
     *
     * @return int
     */
    public function getAttributeId();

    /**
     * Set attribute ID
     *
     * @param int $attributeId
     * @return $this
     */
    public function setAttributeId($attributeId);

    /**
     * Get option id
     *
     * @return int
     */
    public function getOptionValue();

    /**
     * Set option value
     *
     * @param int $optionValue
     * @return $this
     */
    public function setOptionValue($optionValue);

    /**
     * Get dependent attribute ID
     *
     * @return int
     */
    public function getDependentAttributeId();

    /**
     * Set dependent attribute ID
     *
     * @param int $attributeId
     * @return $this
     */
    public function setDependentAttributeId($attributeId);
}
