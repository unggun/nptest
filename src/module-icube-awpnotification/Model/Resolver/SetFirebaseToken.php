<?php

declare(strict_types=1);

namespace Icube\AwpNotification\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class SetFirebaseToken implements ResolverInterface
{

    public function __construct(
        \Icube\AwpNotification\Model\SubscriberDataFactory $subsFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->subsFactory = $subsFactory;
        $this->customerCollectionFactory =  $customerCollectionFactory;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $customerId = $this->getCustomerByPhone(@$args['input']['phonenumber']);
        if($customerId){
            $subscriber = $this->subsFactory->create();
            $subscriber->setToken(@$args['input']['token']);
            $subscriber->setDevice(@$args['input']['device']);
            $subscriber->setCustomerId($customerId);
            $subscriber->save();
            return array('status'=>'success');
        }
        
        return array('status'=>'customer not found');
        
    }

    protected function getCustomerByPhone($phone)
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->getSelect()->where("telephone = '".$phone."'");
        $customer = $customerCollection->getFirstItem();
        if($customer->getId()){
            return $customer->getId();
        }
        return null;
    }
}
