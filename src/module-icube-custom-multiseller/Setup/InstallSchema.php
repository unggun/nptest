<?php

namespace Icube\CustomMultiseller\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('quote_item'),
            'seller_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 255,
                'after' => 'store_id',
                'nullable' => true,
                'comment' => 'Seller Id'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('quote_item'),
            'seller_name',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'after' => 'seller_id',
                'nullable' => true,
                'comment' => 'Seller Name'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('quote_item'),
            'seller_city',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'after' => 'seller_name',
                'nullable' => true,
                'comment' => 'Seller City'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_item'),
            'seller_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 255,
                'after' => 'store_id',
                'nullable' => true,
                'comment' => 'Seller Id'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_item'),
            'seller_name',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'after' => 'seller_id',
                'nullable' => true,
                'comment' => 'Seller Name'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_item'),
            'seller_city',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'after' => 'seller_name',
                'nullable' => true,
                'comment' => 'Seller City'
            ]
        );


        $installer->endSetup();
    }
}
