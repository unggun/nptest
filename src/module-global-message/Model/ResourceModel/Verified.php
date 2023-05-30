<?php
/**
 * Copyright © 2017 Icube. All rights reserved.
 */

namespace Icube\GlobalMessage\Model\ResourceModel;

class Verified extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('verified', 'id');
    }
}
