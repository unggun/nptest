<?php
namespace Icube\TierPrice\Model\ResourceModel;

class TierPrice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('icube_tier_price', 'tier_discount_id');
    }
}
