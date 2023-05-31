<?php

namespace Icube\AwpNotification\Setup;

use \Magento\Framework\Setup\UpgradeSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup,ModuleContextInterface $context) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {

            // Get module table
            $table = $setup->getTable('icube_notificationfirebase_subscriber');
            $connection = $setup->getConnection();

            // Check if the table already exists
            if ($connection->isTableExists($table) == true) {
                
                $connection->modifyColumn(
                    $table,
                    'token',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => '255',
                    ]
                );

                $connection->modifyColumn(
                    $table,
                    'device',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => '255',
                    ]
                );

                 
            }

            $table = $setup->getTable('icube_pushnotificationfirebase');
            if ($connection->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'short_desc',
                    [
                        'type' => Table::TYPE_TEXT,
                        'afters' => 'desc',
                        'comment' => 'Short Description'
                    ],
                    
                );
            }

            $table = $setup->getTable('icube_notificationfirebase_schedule');
            if ($connection->isTableExists($table) == true) {
                $connection->addColumn(
                    $table,
                    'template_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '10',
                        'after' => 'id',
                        'comment' => 'Template Id'
                    ],
                    
                );
                $connection->addColumn(
                    $table,
                    'inbox_id',
                    [
                        'type' => Table::TYPE_INTEGER,
                        'length' => '10',
                        'after' => 'id',
                        'comment' => 'Inbox Id'
                    ],
                    
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $table = $setup->getTable('icube_pushnotificationfirebase');
             $connection = $setup->getConnection();
              if ($connection->isTableExists($table) == true) {
                
                $connection->modifyColumn(
                    $table,
                    'title',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => '255',
                    ]
                );

                $connection->modifyColumn(
                    $table,
                    'desc',
                    [
                        'type' => Table::TYPE_TEXT,
                    ]
                );

                 
            }
        }

        $setup->endSetup();
    }
}