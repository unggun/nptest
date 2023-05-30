<?php

namespace Icube\TierPrice\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

class QuoteSubmitBefore implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // Initialize observed quote and order
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $order->setData('nett', $quote->getNett());

        $quoteItems = $quote->getAllItems();
        $orderItems = $order->getAllItems();

        foreach ($quoteItems as $quoteItem) {
            // Initialize quote item id
            $quoteItemId = $quoteItem->getId();
            foreach ($orderItems as $orderItem) {
                // Initialize order item quote id
                $orderItemQuoteId = $orderItem->getQuoteItemId();
                
                if ($quoteItemId == $orderItemQuoteId && $quoteItem->getTierPriceData()) {
                    if ($quoteItem->getTierPriceData()) {
                        $orderItem->setData('tier_price_data', $quoteItem->getTierPriceData());
                    }

                    if ($quoteItem->getQtyTierPrice()) {
                        $orderItem->setData('qty_tier_price', $quoteItem->getQtyTierPrice());
                    }

                    break;
                }
            }
        }
    }
}
