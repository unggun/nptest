<?php

declare(strict_types=1);

namespace Icube\CustomMultiseller\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;

/**
 * Applied Amasty Extra Fee resolver
 */
class SellerCart implements ResolverInterface
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        CollectionFactory $orderCollectionFactory,
        Collection $orderItemCollection
    ) {
        $this->resource = $resource;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productRepository = $productRepository;
        $this->orderItemCollection = $orderItemCollection;
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
        $customerId = $context->getUserId();
        $cartModel = $value['model'];
        return $this->getSellerData($cartModel, $customerId);
    }

    public function getSellerData($cartModel, $customerId)
    {
        $quoteItems = $cartModel->getItems();
        $data = [];
        foreach ($quoteItems as $item) {
            $sellerId = $item->getSellerId();
            $sellerCity = $item->getSellerCity();
            $sellerName = $item->getSellerName();
            if ($sellerId) {
                $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);

                $themeTable = $this->resource->getTableName('icube_sellerconfig');
                $sql = sprintf("SELECT `type`,`value` FROM %s
                    WHERE (`type` = 'max_order' OR `type` = 'min_order' OR `type` = 'model' OR `type` = 'sla_delivery')
                    AND `seller_id`=:id", $themeTable);
                $queryResult = $connection->fetchAssoc($sql, [':id' => $sellerId]);

                $isExist = 0;
                for ($i = 0; $i < sizeof($data); $i++) {
                    if ($data[$i]['seller_id'] == $sellerId) {
                        $isExist = 1;
                        $data[array_search($sellerId, array_column($data, 'seller_id'))]['seller_total_price'] += $item->getRowTotal();
                    }
                }

                if (!$isExist) {
                    $data[] = [
                        "seller_id" => $sellerId,
                        "seller_name" => $sellerName ?? null,
                        "seller_city" => $sellerCity,
                        "seller_total_price" => $item->getRowTotal(),
                        "seller_max_order" => $queryResult['max_order']['value'] ?? 0,
                        "seller_min_order" => $queryResult['min_order']['value'] ?? 0,
                        "seller_type" => $queryResult['model']['value'] ?? '',
                        "seller_sla_delivery" => $queryResult['sla_delivery']['value'] ?? '',
                    ];
                }
            }
        }

        for ($i = 0; $i < sizeof($data); $i++) {
            $data[$i]['seller_min_order_status'] = $data[$i]['seller_total_price'] >= $data[$i]['seller_min_order'];
            $data[$i]['seller_max_order_status'] = $this->getTodayOrderAmount($data[$i]['seller_id'], $customerId, $data[$i]['seller_max_order']);
        }

        return $data;
    }

    public function getTodayOrderAmount($sellerId, $customerId, $maxOrderPrice)
    {
        $from = date('Y-m-d') . " 00:00:00";
        $to = date('Y-m-d') . " 23:59:59";

        $todayPurchase = 0;
        $orderIds = $this->orderCollectionFactory
            ->create($customerId)
            ->addFieldToFilter(
                'status',
                ['in' => [
                    "complete",
                    "payment_confirmed",
                    "payment_review",
                    "pending",
                    "pending_payment",
                    "pending_paypal",
                    "processing",
                    "ready_to_ship",
                    "shipped"
                ]]
            )
            ->addFieldToFilter('created_at', array('from' => $from, 'to' => $to))
            ->getAllIds();

        $orderItemCollection = $this->orderItemCollection
            ->addFieldToFilter('order_id', ["in" => $orderIds])
            ->addFieldToFilter('seller_id', $sellerId);

        foreach ($orderItemCollection as $item) {
            $todayPurchase += $item->getRowTotal();
        }
        return $todayPurchase < $maxOrderPrice;
    }
}
