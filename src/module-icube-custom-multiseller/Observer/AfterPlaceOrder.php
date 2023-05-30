<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\CustomMultiseller\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterPlaceOrder implements ObserverInterface
{
    public function __construct(
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItem,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->itemFactory = $itemFactory;
        $this->orderItemRepo = $orderItem;
        $this->resource = $resource;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderItems = $observer->getEvent()->getOrder()->getItems();
        foreach ($orderItems as $items) {
            $product = $this->productRepository->getById($items->getProductId());
            $sellerId = $product->getSellerId();
            if ($sellerId) {
                $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                $themeTable = $this->resource->getTableName('icube_multiseller');
                $sql = "SELECT * FROM " . $themeTable . " where id = '" . $sellerId . "'";
                $resultSeller = $connection->fetchAll($sql);

                $seller_cities = explode(",", $resultSeller[0]["city"]);
                $seller_city = $seller_cities[0];
                $itemId =  $items->getItemId();

                $item = $this->orderItemRepo->get($itemId);

                if (!$item || ($item->getData('seller_id') && $item->getData('seller_name') && $item->getData('seller_city'))) {
                    continue;
                }
                $item->setData('seller_id', $sellerId);
                $item->setData('seller_name', $resultSeller[0]["name"] ?? null);
                $item->setData('seller_city', $seller_city);
                $item->save();
            }
        }
    }
}
