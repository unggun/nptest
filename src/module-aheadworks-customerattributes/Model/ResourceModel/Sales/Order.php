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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Sales;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;

/**
 * Class Order
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Sales
 */
class Order extends AbstractDb
{
    /**
     * {@inheritDoc}
     */
    protected $_isPkAutoIncrement = false;

    /**
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init(Attribute::ORDER_ATTRIBUTE_TABLE_NAME, Attribute::ORDER_ID);
    }
}
