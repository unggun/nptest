<?php

namespace Icube\CustomerGroups\Model\Resolver;

use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */

class GetListCustomerGroups implements ResolverInterface
{
    protected $_customerGroup;

    public function __construct(
        Collection $customerGroup
    ) {
        $this->_customerGroup = $customerGroup;
    }

    /**
     * @param Field $field
     * @param [type] $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        try {
            $output[] = [
                'customer_group_id' => null,
                'customer_group_code' => 'ALL GROUPS'
            ];
            $customerGroups = $this->_customerGroup->toOptionArray();

            foreach ($customerGroups as $group) {
                array_push($output, [
                    'customer_group_id' => $group['value'],
                    'customer_group_code' => $group['label']
                ]);
            }

            return $output;
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        }
    }
}
