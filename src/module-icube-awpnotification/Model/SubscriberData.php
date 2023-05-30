<?php


namespace Icube\AwpNotification\Model;

class SubscriberData extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Icube\AwpNotification\Model\ResourceModel\SubscriberData');
    }
}
