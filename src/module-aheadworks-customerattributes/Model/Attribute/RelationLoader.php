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
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;

/**
 * Class RelationLoader
 * @package Aheadworks\CustomerAttributes\Model\Attribute
 */
class RelationLoader
{
    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var array
     */
    private $relationsData;

    /**
     * @param AttributeResource $attributeResource
     */
    public function __construct(
        AttributeResource $attributeResource
    ) {
        $this->attributeResource = $attributeResource;
    }

    /**
     * Retrieve relations data
     *
     * @param bool $prepareForJs
     * @return array
     */
    public function getRelationsData($prepareForJs = true)
    {
        if ($this->relationsData === null) {
            $relationsData = $this->attributeResource->loadAllRelationsData();
            if ($prepareForJs) {
                $relationsData = $this->prepareForJs($relationsData);
            }
            $this->relationsData = $relationsData;
        }

        return $this->relationsData;
    }

    /**
     * Prepare for js
     *
     * @param array $relationsData
     * @return array
     */
    private function prepareForJs($relationsData)
    {
        $prepared = [];

        foreach ($relationsData as $relationData) {
            $attrCode = $relationData[AttributeRelationInterface::ATTRIBUTE_CODE];
            $optionValue = $relationData[AttributeRelationInterface::OPTION_VALUE];
            $depAttrCode = $relationData[AttributeRelationInterface::DEPENDENT_ATTRIBUTE_CODE];
            if (!isset($prepared[$attrCode])) {
                $prepared[$attrCode] = [];
            }
            if (!isset($prepared[$attrCode][$optionValue])) {
                $prepared[$attrCode][$optionValue] = [];
            }
            $prepared[$attrCode][$optionValue][] = $depAttrCode;
        }

        return $prepared;
    }
}
