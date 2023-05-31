<?php
declare(strict_types=1);

namespace Icube\CatalogRestrictions\Model\ResourceModel\SellerConfig;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Icube\CatalogRestrictions\Model\SellerConfig::class,
            \Icube\CatalogRestrictions\Model\ResourceModel\SellerConfig::class
        );
    }
}
