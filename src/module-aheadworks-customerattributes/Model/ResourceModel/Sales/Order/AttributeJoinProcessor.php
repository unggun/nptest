<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Magento\Framework\Data\Collection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

/**
 * Class AttributeJoinProcessor
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order
 */
class AttributeJoinProcessor
{
    const FLAG_NAME = 'aw_order_attr_joined';
    
    /**
     * Join attribute values
     *
     * @param OrderGridCollection|Collection $collection
     * @return void
     */
    public function joinAttributeValues($collection)
    {
        if ($collection instanceof OrderGridCollection && !$collection->hasFlag(self::FLAG_NAME)) {
            $collection->getSelect()
                ->joinLeft(
                    ['aw_order_attr' => $collection->getTable(Attribute::ORDER_ATTRIBUTE_TABLE_NAME)],
                    'main_table.entity_id = aw_order_attr.order_id'
                );
            $collection->setFlag(self::FLAG_NAME, true);
        }
    }
}
