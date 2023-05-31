<?php

namespace Icube\OverrideAdvancerate\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $connection->addColumn(
            $setup->getTable('advance_rate'),
            'vendor_id',
            [
                'type' => Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => false,
                'comment' => 'Vendor Id', 
            ]
        );

        $connection->addColumn(
            $setup->getTable('advance_rate'),
            'wilayah',
            [
                'type' => Table::TYPE_INTEGER,
                'length' => 11,
                'nullable' => true,
                'comment' => 'Wilayah', 
            ]
        );

        $setup->endSetup();
    }
}
