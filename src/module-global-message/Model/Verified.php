<?php

namespace Icube\GlobalMessage\Model;

class Verified extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Icube\GlobalMessage\Model\ResourceModel\Verified');
    }
}
