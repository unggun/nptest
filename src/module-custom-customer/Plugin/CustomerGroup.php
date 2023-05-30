<?php

namespace Icube\CustomCustomer\Plugin;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;

class CustomerGroup
{
    const DEFAULT_GROUP_NAME = 'b2c';

    /**
     * @var \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    protected $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Set verification status mapping
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $customer
     * @param string $hash
     * @param string $redirectUrl
     * @return array
     */
    public function beforeCreateAccountWithPasswordHash(
        AccountManagementInterface $subject,
        CustomerInterface $customer,
        $hash,
        $redirectUrl
    ) {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('customer_group');
        $select = $connection->select()->from($table, 'customer_group_id')->where('customer_group_code = :code');
        $defaultGroupId = $connection->fetchOne($select, [':code' => self::DEFAULT_GROUP_NAME]);
        $customer->setGroupId($defaultGroupId);
        return [$customer, $hash, $redirectUrl];
    }
}
