<?php

namespace Icube\GlobalMessage\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Create customer account resolver
 */
class GetCustomerVerification implements ResolverInterface
{
    public function __construct(
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $customerId = $context->getUserId();
        $attributeCode = "verification_status";

        $customer = $this->customerRepository->getById($customerId);
        return $customer->getCustomAttribute($attributeCode) ? $this->getLabel($attributeCode,$customer->getCustomAttribute($attributeCode)->getValue()) : null;
    }

    protected function getLabel($attributeCode,$attributeoptionid){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $attribute = $objectManager->create('\Magento\Eav\Model\Config')->getAttribute('customer',$attributeCode);
        $options    = $attribute->getSource()->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $attributeoptionid) {
                return strtolower($option['label']);
            }
        }
    }
}
