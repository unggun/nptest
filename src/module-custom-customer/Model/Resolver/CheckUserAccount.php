<?php
declare(strict_types=1);

namespace Icube\CustomCustomer\Model\Resolver;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class CheckUserAccount implements ResolverInterface
{
    /** @var CollectionFactory */
    protected $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
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
        if (!isset($args['phoneNumber'])) {
            throw new GraphQlInputException(__("Required parameter phoneNumber is missing."));
        }

        $customerCollection = $this->collectionFactory->create()
            ->addAttributeToSelect('wp_code')
            ->addAttributeToFilter('telephone', $args['phoneNumber']);
        $customer = $customerCollection->getFirstItem();
        if (!$customer->getId()) {
            throw new GraphQlNoSuchEntityException(__("The phone number is not found."));
        }
        return [
            'phonenumber' => $args['phoneNumber'],
            'customer_wpcode' => $customer->getWpCode(),
        ];
    }
}
