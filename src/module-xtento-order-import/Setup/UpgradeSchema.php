<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-06-24T18:52:58+00:00
 * File:          app/code/Xtento/OrderImport/Setup/UpgradeSchema.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            // Move cronjobs into separate cron group
            $connection->query(
                "UPDATE " . $setup->getTable('core_config_data') . " 
                    SET path = REPLACE(path, 'crontab/default/jobs/" . \Xtento\OrderImport\Cron\Import::CRON_GROUP . "', 'crontab/" . \Xtento\OrderImport\Cron\Import::CRON_GROUP . "/jobs/" . \Xtento\OrderImport\Cron\Import::CRON_GROUP . "')
                    WHERE path LIKE 'crontab/default/jobs/" . \Xtento\OrderImport\Cron\Import::CRON_GROUP . "%'"
            );
        }

        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            $connection->addColumn(
                $setup->getTable('xtento_orderimport_source'),
                'ftp_ignorepasvaddress',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => false,
                    'length' => 1,
                    'comment' => 'FTP Ignore PASV Address'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.2.3', '<')) {
            $connection->addColumn(
                $setup->getTable('xtento_orderimport_profile_history'),
                'status',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'default' => 0,
                    'length' => 1,
                    'comment' => 'Import Status'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.3.6', '<')) {
            $connection->addColumn(
                $setup->getTable('xtento_orderimport_source'),
                'skip_empty_files',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default' => false,
                    'length' => 1,
                    'comment' => 'Skip Empty Files'
                ]
            );
        }

        $setup->endSetup();
    }
}
