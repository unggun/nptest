<?php
/**
 * Copyright © 2017 Icube. All rights reserved.
 */

namespace Icube\GlobalMessage\Model\ResourceModel\Verified;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Icube\GlobalMessage\Model\Verified', 'Icube\GlobalMessage\Model\ResourceModel\Verified');
    }
}
