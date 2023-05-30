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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface;
use Aheadworks\CustomerAttributes\Model\Attribute as AttributeModel;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\ResourceModel\Attribute as CustomerAttribute;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Attribute
 * @package Aheadworks\CustomerAttributes\Model\Data
 */
class Attribute extends CustomerAttribute
{
    /**#@+
     * Core table names
     */
    const MAIN_TABLE_NAME = 'eav_attribute';
    const OPTION_TABLE_NAME = 'eav_attribute_option';
    const ORDER_TABLE_NAME = 'sales_order';
    const QUOTE_TABLE_NAME = 'quote';
    const ORDER_ADDRESS_TABLE_NAME = 'sales_order_address';
    const QUOTE_ADDRESS_TABLE_NAME = 'quote_address';
    /**#@-*/

    /**#@+
     * Constants defined for module table names
     */
    const ADDITIONAL_TABLE_NAME = 'aw_customer_attributes_attribute';
    const RELATION_TABLE_NAME = 'aw_customer_attributes_attribute_relation';
    const ORDER_ATTRIBUTE_TABLE_NAME = 'aw_customer_attributes_order';
    const QUOTE_ATTRIBUTE_TABLE_NAME = 'aw_customer_attributes_quote';
    const QUOTE_ADDRESS_ATTRIBUTE_TABLE_NAME = 'aw_customer_attributes_quote_address';
    const ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME = 'aw_customer_attributes_order_address';
    /**#@-*/

    /**
     * Order column prefix
     */
    const COLUMN_PREFIX = 'customer_';

    /**#@+
     * Constants defined for data names
     */
    const ORDER_ID = 'order_id';
    const QUOTE_ID = 'quote_id';
    const ADDRESS_ID = 'address_id';
    /**#@-*/

    /**
     * @var array
     */
    private $columnDefinitions = [
        'datetime' => ['type' => Table::TYPE_DATE],
        'int' => ['type' => Table::TYPE_INTEGER],
        'text' => ['type' => Table::TYPE_TEXT],
        'varchar' => ['type' => Table::TYPE_TEXT, 'length' => 255],
        'decimal' => ['type' => Table::TYPE_DECIMAL, 'length' => '12,4']
    ];

    /**
     * Add attribute columns
     *
     * @param AttributeModel $attribute
     * @return $this
     */
    public function addAttributeColumns($attribute)
    {
        if (!$attribute->isStatic() && $attribute->getIsUserDefined()) {
            if ($this->canProcessForOrder($attribute)) {
                if ($attribute->getEntityTypeCode() === CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER) {
                    $this->addAttributeColumn(self::ORDER_ATTRIBUTE_TABLE_NAME, $attribute, self::COLUMN_PREFIX);
                    $this->addAttributeColumn(self::QUOTE_ATTRIBUTE_TABLE_NAME, $attribute, self::COLUMN_PREFIX);
                }
            }
            if ($attribute->getEntityTypeCode() === AddressMetadataInterface::ENTITY_TYPE_ADDRESS) {
                $this->addAttributeColumn(self::ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME, $attribute);
                $this->addAttributeColumn(self::QUOTE_ADDRESS_ATTRIBUTE_TABLE_NAME, $attribute);
            }
        }

        return $this;
    }

    /**
     * Load all relations data
     *
     * @return array
     */
    public function loadAllRelationsData()
    {
        $select = $this->getConnection()->select()
            ->from(
                ['main_table' => $this->getTable(self::RELATION_TABLE_NAME)]
            )->join(
                ['eav_table_main' => $this->getTable(self::MAIN_TABLE_NAME)],
                'main_table.attribute_id = eav_table_main.attribute_id',
                [
                    AttributeRelationInterface::ATTRIBUTE_CODE => 'eav_table_main.attribute_code'
                ]
            )->join(
                ['eav_table_additional' => $this->getTable(self::MAIN_TABLE_NAME)],
                'main_table.dependent_attribute_id = eav_table_additional.attribute_id',
                [
                    AttributeRelationInterface::DEPENDENT_ATTRIBUTE_CODE => 'eav_table_additional.attribute_code'
                ]
            );

        return (array)$this->getConnection()->fetchAll($select);
    }

    /**
     * {@inheritDoc}
     * @param AttributeModel $object
     */
    protected function _saveAdditionalAttributeData(AbstractModel $object)
    {
        parent::_saveAdditionalAttributeData($object);

        $this
            ->saveAdditionalData($object)
            ->saveRelationData($object);

        return $this;
    }

    /**
     * Delete attribute columns
     *
     * @param AttributeModel $attribute
     * @return $this
     */
    public function deleteAttributeColumns($attribute)
    {
        if (!$attribute->isStatic() && $attribute->getIsUserDefined()) {
            if ($attribute->getEntityTypeCode() === CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER) {
                $this->dropAttributeColumn(self::QUOTE_ATTRIBUTE_TABLE_NAME, $attribute, self::COLUMN_PREFIX);
                $this->dropAttributeColumn(self::ORDER_ATTRIBUTE_TABLE_NAME, $attribute, self::COLUMN_PREFIX);
            }
            if ($attribute->getEntityTypeCode() === AddressMetadataInterface::ENTITY_TYPE_ADDRESS) {
                $this->dropAttributeColumn(self::ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME, $attribute);
                $this->dropAttributeColumn(self::QUOTE_ADDRESS_ATTRIBUTE_TABLE_NAME, $attribute);
            }
        }

        return $this;
    }

    /**
     * Save additional specific data
     *
     * @param AttributeModel $attribute
     * @return $this
     */
    private function saveAdditionalData($attribute)
    {
        $table = $this->getTable(self::ADDITIONAL_TABLE_NAME);
        $attributeId = $attribute->getAttributeId();
        $connection = $this->getConnection();
        $data = [
            AttributeInterface::ATTRIBUTE_ID => $attributeId,
            AttributeInterface::USED_IN_ORDER_GRID => $attribute->getUsedInOrderGrid(),
            AttributeInterface::USED_IN_ORDER_VIEW => $attribute->getUsedInOrderView()
        ];
        $bind = [':attribute_id' => $attributeId];
        $select = $connection->select()->from(
            $this->getTable($table),
            ['attribute_id']
        )->where('attribute_id = :attribute_id');
        $result = $connection->fetchOne($select, $bind);

        if ($result) {
            $where = ['attribute_id = ?' => $attributeId];
            $connection->update($table, $data, $where);
        } else {
            $connection->insert($table, $data);
        }

        return $this;
    }

    /**
     * Save relation specific data
     *
     * @param AttributeModel $attribute
     * @return $this
     */
    private function saveRelationData($attribute)
    {
        $table = $this->getTable(self::RELATION_TABLE_NAME);
        $attributeId = $attribute->getAttributeId();
        $connection = $this->getConnection();
        $data = [];

        /** @var AttributeRelationInterface $relation */
        foreach ((array)$attribute->getAttributeRelations() as $relation) {
            $optionValue = $relation->getOptionValue();
            $dependentAttributeId = $relation->getDependentAttributeId();
            $key = $attributeId . $optionValue . $dependentAttributeId;

            if (isset($data[$key])) {
                continue;
            }
            $data[$key] = [
                AttributeRelationInterface::ATTRIBUTE_ID => $attributeId,
                AttributeRelationInterface::OPTION_VALUE => $optionValue,
                AttributeRelationInterface::DEPENDENT_ATTRIBUTE_ID => $dependentAttributeId
            ];
        }

        $connection->delete($this->getTable($table), 'attribute_id = ' . $attributeId);
        if (!empty($data)) {
            $connection->insertMultiple($table, array_values($data));
        }

        return $this;
    }

    /**
     * Check is can process attribute for order
     *
     * @param AttributeModel $attribute
     * @return bool
     */
    private function canProcessForOrder($attribute)
    {
        return !in_array($attribute->getFrontendInput(), [InputType::FILE, InputType::IMAGE])
            && (in_array(UsedInForms::ADMIN_CHECKOUT, $attribute->getUsedInForms())
                || $attribute->getUsedInOrderGrid()
                || $attribute->getUsedInOrderView());
    }

    /**
     * Add attribute column
     *
     * @param string $table
     * @param AttributeModel $attribute
     * @param string $prefix
     * @return $this
     */
    private function addAttributeColumn($table, $attribute, $prefix = '')
    {
        $backendType = $attribute->getBackendType();

        if (isset($this->columnDefinitions[$backendType])) {
            $columnDefinition = $this->columnDefinitions[$backendType];
            $columnDefinition['comment'] = $attribute->getFrontendLabel();
            $this->getConnection()->addColumn(
                $this->getTable($table),
                $prefix . $attribute->getAttributeCode(),
                $columnDefinition
            );
        }

        return $this;
    }

    /**
     * Drop attribute column
     *
     * @param string $table
     * @param AttributeModel $attribute
     * @param string $prefix
     * @return $this
     */
    private function dropAttributeColumn($table, $attribute, $prefix = '')
    {
        $this->getConnection()->dropColumn(
            $this->getTable($table),
            $prefix . $attribute->getAttributeCode()
        );

        return $this;
    }
}
