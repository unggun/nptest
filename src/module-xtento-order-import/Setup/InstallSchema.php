<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-05-02T14:40:23+00:00
 * File:          app/code/Xtento/OrderImport/Setup/InstallSchema.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    // @codingStandardsIgnoreStart
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // @codingStandardsIgnoreEnd
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'xtento_orderimport_source'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('xtento_orderimport_source')
        )->addColumn(
            'source_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Name'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Type'
        )->addColumn(
            'hostname',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Hostname'
        )->addColumn(
            'port',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            5,
            ['nullable' => true, 'unsigned' => true],
            'Port'
        )->addColumn(
            'username',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Username'
        )->addColumn(
            'password',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Password'
        )->addColumn(
            'timeout',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            5,
            ['nullable' => false, 'default' => 15],
            'Timeout'
        )->addColumn(
            'path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Path'
        )->addColumn(
            'filename_pattern',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => '//'],
            'Filename Pattern'
        )->addColumn(
            'archive_path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Archive Path'
        )->addColumn(
            'delete_imported_files',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false],
            'Delete imported files'
        )->addColumn(
            'ftp_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false],
            'FTP Server Type'
        )->addColumn(
            'ftp_pasv',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false],
            'FTP Use Passive Mode'
        )->addColumn(
            'custom_class',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Custom Class'
        )->addColumn(
            'custom_function',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Custom Function'
        )->addColumn(
            'last_result',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false],
            'Last Result'
        )->addColumn(
            'last_result_message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65535,
            ['nullable' => false],
            'Last Result Message'
        )->addColumn(
            'last_modification',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Last Modification'
        )->setComment(
            'Xtento_OrderImport Sources table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'xtento_orderimport_log'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('xtento_orderimport_log')
        )->addColumn(
            'log_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'profile_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Profile ID'
        )->addColumn(
            'files',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16777215,
            ['nullable' => false],
            'Imported Files'
        )->addColumn(
            'source_ids',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65535,
            ['nullable' => false],
            'Source IDs'
        )->addColumn(
            'import_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            5,
            ['nullable' => false],
            'Import Type (ID)'
        )->addColumn(
            'import_event',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Import Event'
        )->addColumn(
            'records_imported',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            8,
            ['nullable' => false],
            'Records Imported'
        )->addColumn(
            'result',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            2,
            ['nullable' => false],
            'Import Result'
        )->addColumn(
            'result_message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16777215,
            ['nullable' => false],
            'Import Result Message'
        )->addIndex(
            $installer->getIdxName(
                'xtento_orderimport_log',
                ['profile_id', 'created_at'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['profile_id', 'created_at'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX]
        )->setComment(
            'Xtento_OrderImport Log table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'xtento_orderimport_profile'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('xtento_orderimport_profile')
        )->addColumn(
            'profile_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'entity',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Import Entity'
        )->addColumn(
            'processor',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Import Processor'
        )->addColumn(
            'enabled',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false],
            'Profile Enabled'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65535,
            ['nullable' => false],
            'Profile Name'
        )->addColumn(
            'source_ids',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65535,
            ['nullable' => false],
            'Source IDs'
        )->addColumn(
            'last_execution',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Last Execution'
        )->addColumn(
            'last_modification',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Last Modification'
        )->addColumn(
            'conditions_serialized',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16777215,
            ['nullable' => false],
            'Conditions / Filters'
        )->addColumn(
            'cronjob_enabled',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 0],
            'Cronjob import enabled'
        )->addColumn(
            'cronjob_frequency',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65535,
            ['nullable' => false],
            'Cronjob frequency'
        )->addColumn(
            'cronjob_custom_frequency',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65535,
            ['nullable' => false],
            'Cronjob custom frequency expression'
        )->addColumn(
            'configuration',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16777215,
            ['nullable' => false],
            'Configuration'
        )->setComment(
            'Xtento_OrderImport Profile table'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'xtento_orderimport_profile_history'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('xtento_orderimport_profile_history')
        )->addColumn(
            'history_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'profile_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Profile ID'
        )->addColumn(
            'log_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Log ID'
        )->addColumn(
            'entity',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Import Entity'
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Entity ID'
        )->addColumn(
            'increment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Increment ID'
        )->addColumn(
            'ext_order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'External Order ID'
        )->addColumn(
            'log',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16777215,
            ['nullable' => false],
            'Log'
        )->addColumn(
            'import_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16777215,
            ['nullable' => false],
            'Import Data'
        )->addColumn(
            'imported_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Imported At'
        )->addIndex(
            $installer->getIdxName(
                'xtento_orderimport_profile_history',
                ['profile_id', 'imported_at'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['profile_id', 'imported_at'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX]
        )->addIndex(
            $setup->getIdxName('xtento_orderimport_profile_history', ['entity_id']),
            ['entity_id']
        )->setComment(
            'Xtento_OrderImport Log table'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
