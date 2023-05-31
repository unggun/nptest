<?php
namespace Icube\AwpNotification\Model\ResourceModel\SubscriberData;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Icube\AwpNotification\Model\SubscriberData', 'Icube\AwpNotification\Model\ResourceModel\SubscriberData');
    }
}
