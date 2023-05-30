<?php
namespace Icube\AwpNotification\Model\ResourceModel\ScheduleData;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Icube\AwpNotification\Model\ScheduleData', 'Icube\AwpNotification\Model\ResourceModel\ScheduleData');
    }
}
