<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\CustomMultiseller\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResourceConnection;

/**
 * Orders data reslover
 * Override of \Swiftoms\Multiseller\Model\Resolver\OrdersFilter
 */
class OrdersFilter implements ResolverInterface
{
    /**
     * @var CollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @param CollectionFactoryInterface $collectionFactory
     * @param Order $order
     */
    public function __construct(
        CollectionFactoryInterface $collectionFactory,
        Order $order,
        ResourceConnection $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->order = $order;
        $this->resource = $resource;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer() && isset($args['filters']['email'])) {
            $email = $args['filters']['email'];
            $orders = $this->collectionFactory
            ->create($context->getUserId())
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id',[$args['filters']['ids']])
                ->addFieldToFilter('customer_email', $email);
        } else {
            $orders = $this->collectionFactory
            ->create($context->getUserId())
                ->addFieldToSelect('*')
                ->addFieldToFilter('increment_id',[$args['filters']['ids']]);
        }

        $items = [];
        
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $sellerId = $sellerName = $sellerCity = null;
            if (isset($order->getAllItems()[0]) && $order->getAllItems()[0]->getProduct()) {
                $sellerId = $order->getAllItems()[0]->getProduct()->getSellerId();

                $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                $themeTable = $this->resource->getTableName('icube_multiseller');
                $sql = $connection->select()->from($themeTable, ['id','city','name'])->where('id = :id');
                $result = $connection->fetchAll($sql, [':id' => $sellerId]);

                if ($result) {
                    $sellerCities = explode (",", $result[0]["city"]);
                    $sellerCity = $sellerCities[0];
                    $sellerName = $result[0]["name"];
                }
            }
            
            $items[] = [
                'id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'order_number' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'status' => $order->getStatus(),
                'status_label' => $order->getStatusLabel(),
                'seller_id' => $sellerId,
                'seller_name' => $sellerName,
                'seller_city' => $sellerCity
            ];
        }

        $result = [
            'data' => $items,
        ];
        return $result;
    }
}