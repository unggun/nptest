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
namespace Aheadworks\CustomerAttributes\Setup;

use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table as DataDefinition;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;

/**
 * Class InstallSchema
 *
 * @package Aheadworks\CustomerAttributes\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this
            ->createAttributeTable($installer)
            ->createAttributeRelationTable($installer)
            ->createOrderAttributeTable($installer)
            ->createQuoteAttributeTable($installer)
            ->createOrderAddressAttributeTable($installer)
            ->createQuoteAddressAttributeTable($installer);

        $installer->endSetup();
    }

    /**
     * Create attribute table
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createAttributeTable(SchemaSetupInterface $installer)
    {
        $table = $installer
            ->getConnection()
            ->newTable(
                $installer->getTable(AttributeResource::ADDITIONAL_TABLE_NAME)
            )->addColumn(
                AttributeInterface::ATTRIBUTE_ID,
                DataDefinition::TYPE_SMALLINT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Attribute ID'
            )->addColumn(
                AttributeInterface::USED_IN_ORDER_GRID,
                DataDefinition::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default' => '0'
                ],
                'Is used in order grid'
            )->addColumn(
                AttributeInterface::USED_IN_ORDER_VIEW,
                DataDefinition::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default' => '0'
                ],
                'Is used in order view'
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::ADDITIONAL_TABLE_NAME,
                    AttributeInterface::ATTRIBUTE_ID,
                    AttributeResource::MAIN_TABLE_NAME,
                    AttributeInterface::ATTRIBUTE_ID
                ),
                AttributeInterface::ATTRIBUTE_ID,
                $installer->getTable(AttributeResource::MAIN_TABLE_NAME),
                AttributeInterface::ATTRIBUTE_ID,
                DataDefinition::ACTION_CASCADE
            )->setComment('AW Customer Attributes Attribute Table');
        $installer->getConnection()->createTable($table);
        
        return $this;
    }

    /**
     * Create attribute relation table
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createAttributeRelationTable(SchemaSetupInterface $installer)
    {
        $table = $installer
            ->getConnection()
            ->newTable(
                $installer->getTable(AttributeResource::RELATION_TABLE_NAME)
            )->addColumn(
                AttributeRelationInterface::ATTRIBUTE_ID,
                DataDefinition::TYPE_SMALLINT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Attribute ID'
            )->addColumn(
                AttributeRelationInterface::OPTION_VALUE,
                DataDefinition::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Option value'
            )->addColumn(
                AttributeRelationInterface::DEPENDENT_ATTRIBUTE_ID,
                DataDefinition::TYPE_SMALLINT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Dependent attribute ID'
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::RELATION_TABLE_NAME,
                    AttributeRelationInterface::ATTRIBUTE_ID,
                    AttributeResource::MAIN_TABLE_NAME,
                    AttributeInterface::ATTRIBUTE_ID
                ),
                AttributeRelationInterface::ATTRIBUTE_ID,
                $installer->getTable(AttributeResource::MAIN_TABLE_NAME),
                AttributeRelationInterface::ATTRIBUTE_ID,
                DataDefinition::ACTION_CASCADE
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::RELATION_TABLE_NAME,
                    AttributeRelationInterface::DEPENDENT_ATTRIBUTE_ID,
                    AttributeResource::MAIN_TABLE_NAME,
                    AttributeInterface::ATTRIBUTE_ID
                ),
                AttributeRelationInterface::DEPENDENT_ATTRIBUTE_ID,
                $installer->getTable(AttributeResource::MAIN_TABLE_NAME),
                AttributeInterface::ATTRIBUTE_ID,
                DataDefinition::ACTION_CASCADE
            )->setComment('AW Customer Attributes Attribute Relation Table');
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create order attribute table
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createOrderAttributeTable(SchemaSetupInterface $installer)
    {
        $table = $installer
            ->getConnection()
            ->newTable(
                $installer->getTable(AttributeResource::ORDER_ATTRIBUTE_TABLE_NAME)
            )->addColumn(
                AttributeResource::ORDER_ID,
                DataDefinition::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Order ID'
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::ORDER_ATTRIBUTE_TABLE_NAME,
                    AttributeResource::ORDER_ID,
                    AttributeResource::ORDER_TABLE_NAME,
                    'entity_id'
                ),
                AttributeResource::ORDER_ID,
                $installer->getTable(AttributeResource::ORDER_TABLE_NAME),
                'entity_id',
                DataDefinition::ACTION_CASCADE
            )->setComment('AW Customer Attributes Order Attribute Table');
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create quote attribute table
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createQuoteAttributeTable(SchemaSetupInterface $installer)
    {
        $table = $installer
            ->getConnection()
            ->newTable(
                $installer->getTable(AttributeResource::QUOTE_ATTRIBUTE_TABLE_NAME)
            )->addColumn(
                AttributeResource::QUOTE_ID,
                DataDefinition::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Quote ID'
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::QUOTE_ATTRIBUTE_TABLE_NAME,
                    AttributeResource::QUOTE_ID,
                    AttributeResource::QUOTE_TABLE_NAME,
                    'entity_id'
                ),
                AttributeResource::QUOTE_ID,
                $installer->getTable(AttributeResource::QUOTE_TABLE_NAME),
                'entity_id',
                DataDefinition::ACTION_CASCADE
            )->setComment('AW Customer Attributes Quote Attribute Table');
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create order address attribute table
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createOrderAddressAttributeTable(SchemaSetupInterface $installer)
    {
        $table = $installer
            ->getConnection()
            ->newTable(
                $installer->getTable(AttributeResource::ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME)
            )->addColumn(
                AttributeResource::ADDRESS_ID,
                DataDefinition::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Address ID'
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME,
                    AttributeResource::ADDRESS_ID,
                    AttributeResource::ORDER_ADDRESS_TABLE_NAME,
                    'entity_id'
                ),
                AttributeResource::ADDRESS_ID,
                $installer->getTable(AttributeResource::ORDER_ADDRESS_TABLE_NAME),
                'entity_id',
                DataDefinition::ACTION_CASCADE
            )->setComment('AW Customer Attributes Order Address Attribute Table');
        $installer->getConnection()->createTable($table);

        return $this;
    }

    /**
     * Create quote address attribute table
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createQuoteAddressAttributeTable(SchemaSetupInterface $installer)
    {
        $table = $installer
            ->getConnection()
            ->newTable(
                $installer->getTable(AttributeResource::QUOTE_ADDRESS_ATTRIBUTE_TABLE_NAME)
            )->addColumn(
                AttributeResource::ADDRESS_ID,
                DataDefinition::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Address ID'
            )->addForeignKey(
                $installer->getFkName(
                    AttributeResource::QUOTE_ADDRESS_ATTRIBUTE_TABLE_NAME,
                    AttributeResource::ADDRESS_ID,
                    AttributeResource::QUOTE_ADDRESS_TABLE_NAME,
                    AttributeResource::ADDRESS_ID
                ),
                AttributeResource::ADDRESS_ID,
                $installer->getTable(AttributeResource::QUOTE_ADDRESS_TABLE_NAME),
                AttributeResource::ADDRESS_ID,
                DataDefinition::ACTION_CASCADE
            )->setComment('AW Customer Attributes Quote Address Attribute Table');
        $installer->getConnection()->createTable($table);

        return $this;
    }
}
