<?php

namespace Icube\AwpNotification\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $table = $setup->getTable('icube_notificationfirebase_subscriber');
        if($setup->getConnection()->isTableExists($table) != true) {
            $newTable = $setup->getConnection()
            ->newTable($table)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'ID'
            )
            ->addColumn(
                'token',
                Table::TYPE_TEXT,
                50,
                [
                    'nullable' => false
                ],
                'Firebase Token'
            )
            ->addColumn(
                'device',
                Table::TYPE_TEXT,
                100,
                [
                    'nullable' => true
                ],
                'Device'
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable' => false
                ],
                'Customer Id'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false, 
                    'default' => Table::TIMESTAMP_INIT
                ],
                'Created At'
            );
            $setup->getConnection()->createTable($newTable);
        }

        $table = $setup->getTable('icube_notificationfirebase_schedule');
        if($setup->getConnection()->isTableExists($table) != true) {
            $newTable = $setup->getConnection()
            ->newTable($table)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'ID'
            )
            ->addColumn(
                'schedule',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false, 
                ],
                'Schedule'
            )
            ->addColumn(
                'status',
                Table::TYPE_INTEGER,
                1,
                [
                    'nullable' => false
                ],
                'Status'
            )
            ->addColumn(
                'send_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true, 
                ],
                'Send At'
            )
            ->addColumn(
                'topic',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Topic'
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable' => false
                ],
                'Customer Id'
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Title'
            )
            ->addColumn(
                'image',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true
                ],
                'Image'
            )
            ->addColumn(
                'logo',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true
                ],
                'Logo'
            )
            ->addColumn(
                'desc',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true
                ],
                'Description'
            )
            ->addColumn(
                'path',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Path'
            )
            ;
            $setup->getConnection()->createTable($newTable);
        }
        $setup->endSetup();
    }
}
