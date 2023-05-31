<?php
namespace Icube\TierPrice\Model\ResourceModel\TierPrice;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'tier_price_id';
    protected $_eventPrefix = 'Icube_tierPrice_tierPrice_collection';
    protected $_eventObject = 'tier_price_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Icube\TierPrice\Model\TierPrice', 'Icube\TierPrice\Model\ResourceModel\TierPrice');
    }
}
