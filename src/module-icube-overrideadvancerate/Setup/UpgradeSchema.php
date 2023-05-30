<?php

namespace Icube\OverrideAdvancerate\Setup;

use \Magento\Framework\Setup\UpgradeSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup,ModuleContextInterface $context) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {

            $setup->startSetup();

            $connection = $setup->getConnection();

            $connection->addColumn(
                $setup->getTable('advance_rate'),
                'customer_group',
                [
                    'type' => Table::TYPE_INTEGER,
                    'length' => 5,
                    'nullable' => false,
                    'comment' => 'Customer Group', 
                ]
            );

        }

        $setup->endSetup();
    }
}