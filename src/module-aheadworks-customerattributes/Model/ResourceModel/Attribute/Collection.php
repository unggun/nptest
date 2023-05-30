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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Customer\Model\ResourceModel\Attribute\Collection as AttributesCollection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;
use Aheadworks\CustomerAttributes\Model\Attribute as AttributeModel;

/**
 * Class Collection
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute
 */
class Collection extends AttributesCollection
{
    /**
     * {@inheritdoc}
     */
    protected $_map = [
        'fields' => [
            AttributeInterface::ATTRIBUTE_ID => 'main_table.' . AttributeInterface::ATTRIBUTE_ID
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected $_idFieldName = AttributeInterface::ATTRIBUTE_ID;
    
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(AttributeModel::class, AttributeResource::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        $item->afterLoad();

        return parent::beforeAddLoadedItem($item);
    }

    /**
     * {@inheritDoc}
     */
    protected function _afterLoad()
    {
        $this
            ->attachAdditionalData()
            ->attachRelations();

        return parent::_afterLoad();
    }

    /**
     * Add exclude hidden frontend input attribute filter to collection
     *
     * @return $this
     * @codeCoverageIgnore
     */
    public function addExcludeMultiLineAttributes()
    {
        return $this->addFieldToFilter('main_table.frontend_input', ['neq' => InputType::MULTILINE]);
    }

    /**
     * Returns option array
     *
     * @param string $idField
     * @param string $labelField
     * @return array
     */
    public function toOptionsArray($idField, $labelField)
    {
        return $this->_toOptionArray($idField, $labelField);
    }

    /**
     * Attach additional data
     *
     * @return $this
     */
    private function attachAdditionalData()
    {
        $this->attachDataAsValue(
            AttributeResource::ADDITIONAL_TABLE_NAME,
            AttributeInterface::ATTRIBUTE_ID,
            AttributeInterface::USED_IN_ORDER_GRID,
            [AttributeInterface::USED_IN_ORDER_GRID]
        );
        $this->attachDataAsValue(
            AttributeResource::ADDITIONAL_TABLE_NAME,
            AttributeInterface::ATTRIBUTE_ID,
            AttributeInterface::USED_IN_ORDER_VIEW,
            [AttributeInterface::USED_IN_ORDER_VIEW]
        );

        return $this;
    }

    /**
     * Attach relations
     *
     * @return $this
     */
    private function attachRelations()
    {
        $this->attachDataAsArray(
            AttributeResource::RELATION_TABLE_NAME,
            AttributeInterface::ATTRIBUTE_ID,
            AttributeInterface::ATTRIBUTE_RELATIONS,
            [
                AttributeRelationInterface::DEPENDENT_ATTRIBUTE_ID,
                AttributeRelationInterface::OPTION_VALUE
            ]
        );

        return $this;
    }

    /**
     * Attach data as array
     *
     * @param string $table
     * @param string $referenceColumn
     * @param string $resultFieldAlias
     * @param array $fields
     * @return $this
     */
    private function attachDataAsArray($table, $referenceColumn, $resultFieldAlias, $fields)
    {
        $values = $this->selectData($table, $referenceColumn, $fields);
        if (!empty($values)) {
            foreach ($this as $item) {
                $result = [];
                foreach ($values as $valueData) {
                    if ($valueData[$referenceColumn] == $item->getData($referenceColumn)) {
                        $result[] = $valueData;
                    }
                }
                $item->setData($resultFieldAlias, $result);
            }
        }

        return $this;
    }

    /**
     * Attach data as value
     *
     * @param string $table
     * @param string $referenceColumn
     * @param string $resultFieldAlias
     * @param array $fields
     * @return $this
     */
    private function attachDataAsValue($table, $referenceColumn, $resultFieldAlias, $fields)
    {
        $values = $this->selectData($table, $referenceColumn, $fields);
        if (!empty($values)) {
            foreach ($this as $item) {
                foreach ($values as $valueData) {
                    if ($valueData[$referenceColumn] == $item->getData($referenceColumn)) {
                        $item->setData($resultFieldAlias, $valueData[$resultFieldAlias]);
                        break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Select data
     *
     * @param string $table
     * @param string $referenceColumn
     * @param array $fields
     * @return array
     */
    private function selectData($table, $referenceColumn, $fields)
    {
        $ids = $this->getColumnValues($referenceColumn);
        $fields[] = $referenceColumn;
        $values = [];

        if ($ids) {
            $select = $this->getConnection()->select()
                ->from(
                    ['tmp' => $this->getTable($table)],
                    $fields
                )->where($referenceColumn . ' IN (?)', $ids);
            $values = $this->getConnection()->fetchAll($select);
        }

        return $values;
    }
}
