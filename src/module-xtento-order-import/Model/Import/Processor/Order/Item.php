<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-09-10T19:24:18+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Order/Item.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor\Order;

use Magento\Customer\Model\Group;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Xtento\XtCore\Helper\Utils;

class Item extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    protected $giftMessageFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Utils
     */
    protected $utilsHelper;

    /**
     * Item constructor.
     *
     * @param Registry $registry
     * @param \Magento\Tax\Model\Calculation $taxCalculation
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Sales\Model\Order\ItemFactory $orderItemFactory
     * @param \Magento\GiftMessage\Model\MessageFactory $giftMessageFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param Utils $utilsHelper
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        \Magento\GiftMessage\Model\MessageFactory $giftMessageFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Utils $utilsHelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->registry = $registry;
        $this->taxCalculation = $taxCalculation;
        $this->productFactory = $productFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->giftMessageFactory = $giftMessageFactory;
        $this->eventManager = $eventManager;
        $this->utilsHelper = $utilsHelper;
    }

    /**
     * @param $updateData
     *
     * @return array|bool
     */
    public function validate(&$updateData)
    {
        if (!isset($updateData['items']) || !is_array($updateData['items']) || empty($updateData['items'])) {
            return [
                'stop' => false,
                'message' => __(
                    'No order items found for current record. Skipping order item processor.'
                )
            ];
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $updateData
     *
     * @return array|bool
     */
    public function process(&$order, &$updateData)
    {
        if (!isset($updateData['items']) || !is_array($updateData['items']) || empty($updateData['items'])) {
            return true;
        }

        $isOrderNew = $this->isObjectNew($updateData, 'order');

        $this->eventManager->dispatch('xtento_orderimport_item_processor_before', ['order' => $order, 'update_data' => $updateData]);
        $result = $this->importItemData($order, $updateData['items'], $isOrderNew);
        $this->eventManager->dispatch('xtento_orderimport_item_processor_after', ['order' => $order, 'update_data' => $updateData]);

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $items
     * @param $isOrderNew
     *
     * @return array|bool
     */
    protected function importItemData(&$order, &$items, $isOrderNew)
    {
        $warnings = [];

        $isMagentoMsiEnabled = $this->utilsHelper->isExtensionInstalled('Magento_InventorySales');

        foreach ($items as $itemData) {
            // Prepare array
            foreach ($itemData as $key => $value) {
                if (strstr($key, 'order_item|') === false) {
                    continue;
                }
                $itemData[str_replace('order_item|', '', $key)] = $value;
                unset($itemData[$key]);
            }

            $itemExistsAlready = false;
            /* @var $item \Magento\Sales\Model\Order\Item */
            $item = $this->orderItemFactory->create();
            if (isset($itemData['item_id']) && $itemData['item_id'] > 0) {
                $item->load($itemData['item_id']);
                if ($item->getId()) {
                    $itemExistsAlready = true;
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->productFactory->create()->setStoreId($order->getStoreId())->load($item->getProductId());
                    $productId = $product->getId();
                    $warnings[] = __('Item ID %1 referenced, item exists already, will be updated.', $item->getId());
                }
            }

            $productIdentifier = $this->getConfig('product_identifier');
            if (!$itemExistsAlready) {
                // Check product exists
                $uniqueIdentifier = '';
                $productId = null;
                if ($productIdentifier == 'sku') {
                    if (!isset($itemData['sku'])) {
                        $warnings[] = __('SKU not set for product, not importing product.');
                        continue;
                    }
                    $uniqueIdentifier = $itemData['sku'];
                    $productId = $this->productFactory->create()->getIdBySku($itemData['sku']);
                } else {
                    if ($productIdentifier == 'entity_id') {
                        if (!isset($itemData['product_id'])) {
                            $warnings[] = __('Product ID not set for product, not importing product.');
                            continue;
                        }
                        $uniqueIdentifier = $itemData['product_id'];
                        $resource = $this->registry->registry('orderimport_profile')->getResource();
                        $productId = $resource->getConnection()->fetchOne('select entity_id from ' . $resource->getTable('catalog_product_entity') . ' where entity_id=?', $itemData['product_id']);
                    } else {
                        if ($productIdentifier == 'attribute') {
                            $productIdentifierAttributeCode = $this->getConfig('product_identifier_attribute_code');
                            if (!isset($itemData['sku'])) {
                                $warnings[] = __('Custom product attribute to identify products by "%1" not found, not importing product.', $productIdentifierAttributeCode);
                                continue;
                            }
                            $uniqueIdentifier = $itemData['sku'];
                            $collection = $this->productFactory->create()->getCollection();
                            $collection->addAttributeToFilter($productIdentifierAttributeCode, $itemData['sku']);
                            if ($collection->getSize()) {
                                $productId = $collection->getFirstItem()->getId();
                            }
                        }
                    }
                }

                if (!$productId) {
                    if (!$isMagentoMsiEnabled) {
                        $productId = 99999999;
                        $warnings[] = __('Product not found, using fallback product ID 99999999.');
                        $product = $this->productFactory->create();
                    } else {
                        $warnings[] = __('Product %1 (identifier: %2) not found, skipping as Magento MSI is enabled.', $uniqueIdentifier, $productIdentifier);
                        continue;
                    }
                } else {
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->productFactory->create()->setStoreId($order->getStoreId())->load($productId);

                    $skipOutOfStockProducts = $this->getConfigFlag('skip_out_of_stock_products');
                    if ($skipOutOfStockProducts && !$product->isSaleable()) {
                        $warnings[] = __('Product SKU "%1" is out of stock, skipping', $product->getSku());
                        continue;
                    }
                }
                $item->setStoreId($order->getStoreId());
                $item->setProductId($productId);

                // Customer group / Qty requested
                $customerGroupId = Group::NOT_LOGGED_IN_ID;
                $qty = isset($itemData['qty_ordered']) ? $itemData['qty_ordered'] : 1;
                if (!is_numeric($qty)) {
                    $warnings[] = __('Qty of product contains invalid value, falling back to qty 1.');
                    $qty = 1;
                }
                // Get customer group
                $customer = $this->registry->registry('xtento_orderimport_current_customer');
                if ($customer !== null && $customer->getId()) {
                    $customerGroupId = $customer->getGroupId();
                }

                if (!isset($itemData['base_price']) && isset($itemData['price'])) {
                    $itemData['base_price'] = $itemData['price'];
                }
                if (!isset($itemData['price']) && isset($itemData['base_price'])) {
                    $itemData['price'] = $itemData['base_price'];
                }
                if (!isset($itemData['base_price_incl_tax']) && isset($itemData['price_incl_tax'])) {
                    $itemData['base_price_incl_tax'] = $itemData['price_incl_tax'];
                }
                if (!isset($itemData['price_incl_tax']) && isset($itemData['base_price_incl_tax'])) {
                    $itemData['price_incl_tax'] = $itemData['base_price_incl_tax'];
                }

                // Get product price in case it's not set
                if (!isset($itemData['base_price']) && !isset($itemData['price']) && !isset($itemData['base_price_incl_tax']) && !isset($itemData['price_incl_tax']) && !is_null($productId)) {
                    // Calculate final price
                    $finalPrice = false;
                    if ($product->getId()) {
                        $product->setCustomerGroupId($customerGroupId);
                        $finalPrice = $product->getPriceModel()->getFinalPrice($qty, $product);
                        $this->addTaxToProductPrice($order, $itemData, $product, $qty, $finalPrice);
                    }
                    if ($finalPrice === false) {
                        $collection = $this->productFactory->create()->getCollection()
                            ->setStore($order->getStoreId())
                            ->addIdFilter($productId)
                            ->addFinalPrice();
                        foreach ($collection as $product) {
                            $product->setCustomerGroupId($customerGroupId);
                            $product->setStore($order->getStoreId());
                            $finalPrice = $product->getFinalPrice($qty);
                            $itemData['base_price'] = $finalPrice;
                            $itemData['price'] = $finalPrice;
                        }
                    }
                }

                // Price still not found/set, set to 0
                if (!isset($itemData['base_price']) && !isset($itemData['price']) && !isset($itemData['base_price_incl_tax']) && !isset($itemData['price_incl_tax'])) {
                    $itemData['base_price'] = 0;
                    $itemData['base_price_incl_tax'] = 0;
                }
                if (!isset($itemData['price']) && isset($itemData['base_price'])) {
                    $itemData['price'] = $itemData['base_price'];
                }
                if (!isset($itemData['base_price']) && isset($itemData['price'])) {
                    $itemData['base_price'] = $itemData['price'];
                }
                if (!isset($itemData['price_incl_tax']) && isset($itemData['base_price_incl_tax'])) {
                    $itemData['price_incl_tax'] = $itemData['base_price_incl_tax'];
                }
                if (!isset($itemData['base_price_incl_tax']) && isset($itemData['price_incl_tax'])) {
                    $itemData['base_price_incl_tax'] = $itemData['price_incl_tax'];
                }
                if (!isset($itemData['base_tax_amount']) && isset($itemData['tax_amount'])) {
                    $itemData['base_tax_amount'] = $itemData['tax_amount'];
                }
                if (!isset($itemData['tax_amount']) && isset($itemData['base_tax_amount'])) {
                    $itemData['tax_amount'] = $itemData['base_tax_amount'];
                }
                if (!isset($itemData['price']) && isset($itemData['price_incl_tax'])) {
                    $itemData['price'] = $itemData['price_incl_tax'];
                    if (isset($itemData['tax_amount'])) {
                        $itemData['price'] -= $itemData['tax_amount'];
                    }
                }
                if (!isset($itemData['base_price']) && isset($itemData['base_price_incl_tax'])) {
                    $itemData['base_price'] = $itemData['base_price_incl_tax'];
                    if (isset($itemData['base_tax_amount'])) {
                        $itemData['base_price'] -= $itemData['base_tax_amount'];
                    }
                }

                // Tax calculation
                if (!isset($itemData['base_price_incl_tax']) && !isset($itemData['price_incl_tax']) && !is_null($productId)) {
                    $this->addTaxToProductPrice($order, $itemData, $product, $qty, $itemData['base_price']);
                } else {
                    if (!isset($itemData['tax_amount']) && isset($itemData['price'])) {
                        $itemData['tax_amount'] = ((float)$itemData['price_incl_tax'] - (float)$itemData['price']) * $qty;
                    } else {
                        if (!isset($itemData['tax_amount'])) {
                            $itemData['tax_amount'] = 0;
                        }
                    }
                    if (!isset($itemData['base_tax_amount']) && isset($itemData['base_price'])) {
                        $itemData['base_tax_amount'] = ((float)$itemData['base_price_incl_tax'] - (float)$itemData['base_price']) * $qty;
                    } else {
                        if (!isset($itemData['base_tax_amount'])) {
                            $itemData['base_tax_amount'] = 0;
                        }
                    }
                    if ($itemData['base_tax_amount'] == 0 || $itemData['tax_amount'] == 0) {
                        // Calculate tax amount
                        $this->addTaxAmountForItem($order, $itemData, $product, $qty, $itemData['base_price_incl_tax']);
                    }
                }
            }

            foreach ($itemData as $fieldName => $value) {
                if (is_array($value)) {
                    continue;
                }
                if ($fieldName == 'item_id') {
                    $fieldName = 'old_item_id';
                }
                if ($fieldName == 'parent_item_id') {
                    $fieldName = 'old_parent_item_id';
                }
                $ignoredFields = ['quote_item_id', 'product_id', 'order_id', 'gift_message_id'];
                if (array_key_exists($fieldName, $ignoredFields)) {
                    continue;
                }
                $fieldValue = (string)$value;
                if ($fieldName == 'product_options') {
                    try {
                        $fieldValue = json_decode($fieldValue);
                    } catch (\Exception $e) {
                        $fieldValue = [];
                    }
                }
                $item->setData($fieldName, $fieldValue);
                if (!isset($itemData['base_' . $fieldName])) {
                    $item->setData('base_' . $fieldName, $fieldValue);
                }
            }

            if ($productIdentifier == 'attribute' || $productIdentifier == 'entity_id') {
                if ($product->getSku()) $item->setSku($product->getSku());
            }
            if (!isset($itemData['qty_ordered']) && !$item->getQtyOrdered()) {
                $item->setQtyOrdered(1);
            }
            if (!isset($itemData['product_type']) && !$item->getProductType()) {
                if ($product->getId()) {
                    $item->setProductType($product->getTypeId());
                } else {
                    $item->setProductType('simple');
                }
            }
            if (!isset($itemData['base_price_incl_tax']) && !$item->getBasePriceInclTax()) {
                $item->setBasePriceInclTax((float)$item->getBasePrice() + (float)$item->getBaseTaxAmount());
            }
            if (!isset($itemData['price_incl_tax']) && !$item->getPriceInclTax()) {
                $item->setPriceInclTax((float)$item->getPrice() + (float)$item->getTaxAmount());
            }
            if (!isset($itemData['base_row_total']) && !$item->getBaseRowTotal()) {
                $item->setBaseRowTotal((float)$item->getBasePrice() * $qty);
            }
            if (!isset($itemData['row_total']) && !$item->getRowTotal()) {
                $item->setRowTotal((float)$item->getPrice() * $qty);
            }
            if (!isset($itemData['base_row_total_incl_tax']) && !$item->getBaseRowTotalInclTax()) {
                $item->setBaseRowTotalInclTax((float)$item->getBasePriceInclTax() * $qty);
            }
            if (!isset($itemData['row_total_incl_tax']) && !$item->getRowTotalInclTax()) {
                $item->setRowTotalInclTax((float)$item->getPriceInclTax() * $qty);
            }

            if (!isset($itemData['name']) && !$item->getName()) {
                if ($product->getId()) {
                    $item->setName($product->getName());
                } else {
                    $item->setName(__('Imported Product'));
                }
            }
            if (!isset($itemData['weight']) && !$item->getWeight()) {
                $item->setWeight($product->getWeight());
            }
            if ($productIdentifier != 'sku' && !$item->getSku()) {
                if ($product->getId()) {
                    $item->setSku($product->getSku());
                } else {
                    $item->setSku($productIdentifier);
                }
            }

            // Child items - not required to have these fields
            if ($item->getOldParentItemId() && !$item->getId()) {
                $item->setBasePriceInclTax(null);
                $item->setPriceInclTax(null);
                $item->setBaseRowTotal(null);
                $item->setRowTotal(null);
                $item->setBaseRowTotalInclTax(null);
                $item->setRowTotalInclTax(null);
            }

            if (empty($item->getProductOptions()) && $item->getProduct()) {
                /*$optionValues = $product->processBuyRequest(new DataObject());
                $optionValues->setQty($qty);
                var_dump($optionValues);
                $product->setPreconfiguredValues($optionValues);*/
                $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
                $item->setProductOptions(
                    array_merge(
                        $options,
                        [
                            'info_buyRequest' => [
                                'qty' => $qty,
                            ],
                        ]
                    )
                );
            }

            // Gift message
            if (isset($itemData['gift_message'])) {
                $giftMessage = $this->giftMessageFactory->create();
                $giftMessageFields = ['gift_message', 'gift_message_from', 'gift_message_to'];
                foreach ($giftMessageFields as $giftMessageField) {
                    $value = (isset($itemData[$giftMessageField]) ? $itemData[$giftMessageField] : '');
                    $giftMessageFieldMapped = '';
                    if ($giftMessageField === 'gift_message') {
                        $giftMessageFieldMapped = 'message';
                    }
                    if ($giftMessageField === 'gift_message_from') {
                        $giftMessageFieldMapped = 'sender';
                    }
                    if ($giftMessageField === 'gift_message_to') {
                        $giftMessageFieldMapped = 'recipient';
                    }
                    $giftMessage->setData($giftMessageFieldMapped, (string)$value);
                }
                $giftMessage->save();
                $item->setGiftMessageId($giftMessage->getId());
            }

            if ($itemExistsAlready) {
                $item->save();
            } else {
                $order->addItem($item);
            }
        }
        //die();

        if (!empty($warnings)) {
            return [
                'stop' => false,
                'message' => implode(", ", $warnings)
            ];
        } else {
            return true;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $product
     *
     * @return float
     */
    protected function getTaxPercent($order, $product)
    {
        $customer = $this->registry->registry('xtento_orderimport_current_customer');
        $taxCalculation = $this->taxCalculation;
        $request = $taxCalculation->getRateRequest($order->getShippingAddress(), $order->getBillingAddress(), $order->getCustomerTaxClassId(), $product->getStore(), $customer ? $customer->getId() : null);
        $taxRateRequest = $request->setProductClassId($product->getTaxClassId());
        $rate = $taxCalculation->getRate($taxRateRequest);
        //$storeRate = $taxCalculation->getStoreRate($taxRateRequest, $product->getStore()); // getRate seems better as it returns "customers" tax rate (some customers are tax exempt etc.)
        return $rate;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $itemData
     * @param $product
     * @param $price
     *
     * @return float|int
     */
    protected function addTaxToProductPrice($order, &$itemData, $product, $qty, $price)
    {
        $priceInclTax = (float)$price;
        $priceExclTax = (float)$price;
        $taxPercent = $this->getTaxPercent($order, $product);
        if ($taxPercent > 0) {
            //if (!Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, $product->getStore())) {
            // Prices are excluding tax -> add tax
            $priceExclTax = $price;
            $priceInclTax = $priceExclTax * (1 + $taxPercent / 100);
            /*} else {
                // Prices are including tax - do not add tax to price
                $priceInclTax = $price;
                $priceExclTax = $priceInclTax / (1 + $taxPercent / 100);
            }*/
        }
        if (!isset($itemData['base_price']) || $itemData['base_price'] == $priceInclTax) {
            $itemData['base_price'] = $priceExclTax;
        }
        if (!isset($itemData['price']) || $itemData['price'] == $priceInclTax) {
            $itemData['price'] = $priceExclTax;
        }
        if (!isset($itemData['tax_amount']) || $itemData['tax_amount'] == 0) $itemData['tax_amount'] = ($priceInclTax - $priceExclTax) * $qty;
        if (!isset($itemData['base_tax_amount']) || $itemData['base_tax_amount'] == 0) $itemData['base_tax_amount'] = ($priceInclTax - $priceExclTax) * $qty;
        if (!isset($itemData['base_price_incl_tax']) || $itemData['base_price_incl_tax'] == 0) $itemData['base_price_incl_tax'] = $itemData['base_price'] + ($itemData['base_tax_amount'] / $qty);
        if (!isset($itemData['price_incl_tax']) || $itemData['price_incl_tax'] == 0) $itemData['price_incl_tax'] = $itemData['price'] + ($itemData['tax_amount'] / $qty);
        if (!isset($itemData['tax_percent'])) $itemData['tax_percent'] = $taxPercent;
        return $price;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $itemData
     * @param $product
     * @param $price
     *
     * @return float|int
     */
    protected function addTaxAmountForItem($order, &$itemData, $product, $qty, $price)
    {
        $priceInclTax = (float)$price;
        $priceExclTax = (float)$price;
        $taxPercent = $this->getTaxPercent($order, $product);
        if ($taxPercent > 0) {
            $priceInclTax = $price;
            $priceExclTax = $price / (1 + $taxPercent / 100);
        }
        if (!isset($itemData['base_price']) || $itemData['base_price'] == $priceInclTax) {
            $itemData['base_price'] = $priceExclTax;
        }
        if (!isset($itemData['price']) || $itemData['price'] == $priceInclTax) {
            $itemData['price'] = $priceExclTax;
        }
        if (!isset($itemData['base_price_incl_tax']) || $itemData['base_price_incl_tax'] == 0) $itemData['base_price_incl_tax'] = $priceInclTax;
        if (!isset($itemData['price_incl_tax']) || $itemData['price_incl_tax'] == 0) $itemData['price_incl_tax'] = $priceInclTax;
        if (!isset($itemData['tax_amount']) || $itemData['tax_amount'] == 0) $itemData['tax_amount'] = ($priceInclTax - $priceExclTax) * $qty;
        if (!isset($itemData['base_tax_amount']) || $itemData['base_tax_amount'] == 0) $itemData['base_tax_amount'] = ($priceInclTax - $priceExclTax) * $qty;
        if (!isset($itemData['tax_percent'])) $itemData['tax_percent'] = $taxPercent;
        return $price;
    }

}