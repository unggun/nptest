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
 * Orders data resolver
 * Override of \Swiftoms\Multiseller\Model\Resolver\Orders
 */
class Orders implements ResolverInterface
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        // init var
        $pageSize       = $args['pageSize'];
        $currentPage    = $args['currentPage'];

        $items = [];

        if (isset($args['filters']['ids'])) {
            $orders = $this->collectionFactory->create($context->getUserId())->addFieldToSelect('*')
                ->addFieldToFilter(
                    'increment_id',
                    [$args['filters']['ids']]
                );
        } else {
            $orders = $this->collectionFactory->create($context->getUserId());
        }

        if (isset($args['filters']['status'])) {
            $orders->addFieldToFilter('status', array('eq' => strtolower($args['filters']['status'])));
        }

        $orders->setOrder('increment_id', 'DESC')->setPageSize($pageSize)->setCurPage($currentPage);
        
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $comments = [];
            foreach ($order->getVisibleStatusHistory() as $status) {
                if ($status->getComment()) {
                    $comments[] = [
                        'timestamp' => $status->getCreatedAt(),
                        'message' => $status->getComment()
                    ];
                }
            }

            $sellerId = $sellerName = $sellerModel = $sellerSla = $sellerCity = null;
            $isDelta = false;
            if (isset($order->getAllItems()[0]) && $order->getAllItems()[0]->getProduct()) {
                $sellerId = $order->getAllItems()[0]->getProduct()->getSellerId();

                $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                $themeTable = $this->resource->getTableName('icube_multiseller');
                $joinTable = $this->resource->getTableName('icube_sellerconfig');
                $sql = sprintf("SELECT im.*,is2.value model,is3.value sla FROM %s im
                            LEFT JOIN %s is2 ON is2.seller_id = im.id AND is2.type = 'model'
                            LEFT JOIN %s is3 ON is3.seller_id = im.id AND is3.type = 'sla_delivery'
                            WHERE im.id = :id", $themeTable, $joinTable, $joinTable);
                $result = $connection->fetchAll($sql, [':id' => $sellerId]);

                if ($result) {
                    $sellerCities = explode(",", $result[0]["city"]);
                    $sellerCity = $sellerCities[0];
                    $sellerName = $result[0]["name"];
                    $sellerModel = $result[0]['model'] ?? '';
                    $sellerSla = $result[0]['sla'] ?? '';
                }
            }

            if ($order->getIsDelta()) {
                $isDelta = true;
            }

            $items[] = [
                'id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'order_number' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'model' => $order,
                'status' => $order->getStatus(),
                'status_label' => $order->getStatusLabel(),
                'comments' => $comments,
                'seller_id' => $sellerId,
                'seller_name' => $sellerName,
                'seller_city' => $sellerCity,
                'seller_type' => $sellerModel,
                'seller_sla_delivery' => $sellerSla,
                'is_delta' => $isDelta
            ];
        }

        //possible division by 0
        if ($pageSize) {
            $maxPages = (int)ceil($orders->getSize() / $pageSize);
        } else {
            $maxPages = 0;
        }

        $result = [
            'items' => $items,
            'total_count' => $orders->getSize(),
            'page_size' => $pageSize,
            'current_page' => $currentPage,
            'total_pages' => $maxPages
        ];
        return $result;
    }
}
