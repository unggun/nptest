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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection as AttributeCollection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Address\Collection as AttributeAddressCollection;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class CollectionFactory
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute
 */
class CollectionFactory
{
    /**
     * ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * @var array
     */
    private $collections = [
        CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER => AttributeCollection::class,
        AddressMetadataInterface::ENTITY_TYPE_ADDRESS => AttributeAddressCollection::class
    ];

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = AttributeCollection::class
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param string $type
     * @param array $data
     * @return AttributeCollection|AttributeAddressCollection
     */
    public function create($type = CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, array $data = [])
    {
        $instanceName = isset($this->collections[$type])
            ? $this->collections[$type]
            : $this->_instanceName;

        return $this->_objectManager->create($instanceName, $data);
    }
}
