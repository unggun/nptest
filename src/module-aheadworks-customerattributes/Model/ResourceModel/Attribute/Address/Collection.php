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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Address;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection as AttributeCollection;

/**
 * Class Collection
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Address
 */
class Collection extends AttributeCollection
{
    /**
     * @var string
     */
    protected $_entityTypeCode = 'customer_address';
}
