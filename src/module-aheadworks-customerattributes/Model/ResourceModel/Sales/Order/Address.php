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

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Aheadworks\CustomerAttributes\Model\Attribute\Formatter\Date as DateFormatter;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Address
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order
 */
class Address extends AbstractDb
{
    /**
     * {@inheritDoc}
     */
    protected $_isPkAutoIncrement = false;

    /**
     * @var DateFormatter
     */
    private $dateFormatter;

    /**
     * @param Context $context
     * @param DateFormatter $dateFormatter
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        DateFormatter $dateFormatter,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dateFormatter = $dateFormatter;
    }

    /**
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init(Attribute::ORDER_ADDRESS_ATTRIBUTE_TABLE_NAME, Attribute::ADDRESS_ID);
    }

    /**
     * {@inheritDoc}
     */
    protected function _prepareTableValueForSave($value, $type)
    {
        if ($type === 'date') {
            $value = $this->dateFormatter->format($value);
        }
        if ($type === 'varchar' && strpos($value, "\n") !== false) {
            $value = str_replace("\n", ",", $value);
        }

        return parent::_prepareTableValueForSave($value, $type);
    }
}
