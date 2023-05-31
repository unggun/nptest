<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\CustomMultiseller\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterAddToCart implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Quote\Model\QuoteRepository $quoteRepo,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->resource = $resource;
        $this->_quoteRepo = $quoteRepo;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event =  $observer->getEvent();
        $items = $event->getData('items');
        foreach ($items as $key => $item) {
            $product = $this->productRepository->getById($item->getProductId());
            $sellerId = $product->getSellerId();

            $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
            $themeTable = $this->resource->getTableName('icube_multiseller');
            $sql = "SELECT * FROM " . $themeTable . " where id = '" . $sellerId . "'";
            $resultSeller = $connection->fetchAll($sql);

            if (!count($resultSeller)) {
                continue;
            }

            $seller_cities = explode(",", $resultSeller[0]["city"]);
            $seller_city = $seller_cities[0];

            $quote = $this->_quoteRepo->get($item->getQuoteId());
            $item = $quote->getItemById($item->getId());
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
