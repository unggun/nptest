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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;

/**
 * Class Uninstall
 * @package Aheadworks\CustomerAttributes\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * {@inheritdoc}
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $this->uninstallTables($installer);
        $installer->endSetup();
    }

    /**
     * Uninstall all module tables
     *
     * @param SchemaSetupInterface $installer
     * @return $this
     */
    private function uninstallTables(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $connection->dropTable($installer->getTable(AttributeResource::QUOTE_ADDRESS_ATTRIBUTE_TABLE_NAME));
        $connection->dropTable($installer->getTable(AttributeResource::ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME));
        $connection->dropTable($installer->getTable(AttributeResource::QUOTE_ATTRIBUTE_TABLE_NAME));
        $connection->dropTable($installer->getTable(AttributeResource::ORDER_ATTRIBUTE_TABLE_NAME));
        $connection->dropTable($installer->getTable(AttributeResource::RELATION_TABLE_NAME));
        $connection->dropTable($installer->getTable(AttributeResource::ADDITIONAL_TABLE_NAME));

        return $this;
    }
}
