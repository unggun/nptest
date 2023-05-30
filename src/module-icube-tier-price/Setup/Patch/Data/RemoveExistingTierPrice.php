<?php

namespace Icube\TierPrice\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class RemoveExistingTierPrice implements DataPatchInterface
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    public function apply()
    {
        $connection = $this->resource->getConnection();
        $tableName = $connection->getTableName('catalog_product_entity_tier_price');
        $sql = "DELETE FROM " . $tableName;
        $connection->query($sql);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getVersion(): string
    {
        return " ";
    }
}
