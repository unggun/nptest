<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\CustomCustomer\Model\Resolver;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Customer is_subscribed field resolver
 */
class GetWpCode implements ResolverInterface
{
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @param SubscriberFactory $subscriberFactory
     */
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
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var CustomerInterface $customer */
        $customer = $value['model'];
        $customerId = (int)$customer->getId();
        $customer = $this->customerRepository->getById($customerId);
        $wpCode = null;
        try {
            $wpCode = $customer->getCustomAttribute('wp_code');
            if ($wpCode) {
                $wpCode = $wpCode->getValue();
            }
        } catch (\Exception $e) {
            $wpCode = null;
        }

        return $wpCode;
    }
}
