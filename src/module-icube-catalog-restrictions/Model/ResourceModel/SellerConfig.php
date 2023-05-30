<?php
declare(strict_types=1);

namespace Icube\CatalogRestrictions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SellerConfig extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('icube_sellerconfig', 'entity_id');
    }
}
