<?php

namespace Icube\CustomCustomer\Plugin;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerSetName
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    private $request;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->resourceConnection = $resourceConnection;
    }
    /**
     * Split firstname 
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     * 
     */
    public function afterCreateAccount(
        AccountManagementInterface $subject,
        CustomerInterface $result
    ) {
        $phonenumber = $result->getCustomAttribute("telephone")->getValue();
        $id          = $result->getId();
        $storeId     = $result->getStoreId();
        
        $prefix = '0';
        $idPrefix = "62";

        if (substr($phonenumber, 0, strlen($idPrefix)) == $idPrefix) {
            $phonenumber = $phonenumber;
        } else {
            if (substr($phonenumber, 0, strlen($prefix)) == $prefix) {
                $phonenumber = "62" . substr($phonenumber, strlen($prefix));
            } else {
                $phonenumber = "62" . $phonenumber;
            }
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('customer_entity');

        $query = "UPDATE `" . $table . "` SET `telephone`= $phonenumber, `whatsapp_number`= $phonenumber WHERE entity_id = $id AND store_id = $storeId ";
        $connection->query($query);
        
        $tempName = $result->getFirstname();
        $csName = explode(" ", $result->getFirstname());
        
        if (count($csName) > 1) {
            $firstName = current($csName);
            $lastName = str_replace($firstName, "", $tempName);
            
            $result->setFirstname($firstName);
            $result->setLastname($lastName);
            $this->customerRepositoryInterface->save($result);
        }

        return $result;
    }
}
