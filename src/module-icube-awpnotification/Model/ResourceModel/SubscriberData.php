<?php


namespace Icube\AwpNotification\Model\ResourceModel;

class SubscriberData extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('icube_notificationfirebase_subscriber', 'id');
    }
}
