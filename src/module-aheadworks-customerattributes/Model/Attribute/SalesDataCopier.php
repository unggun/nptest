<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Model\Attribute;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddress;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;

/**
 * Class SalesDataCopier
 * @package Aheadworks\CustomerAttributes\Model\Attribute
 */
class SalesDataCopier
{
    /**
     * @var Provider
     */
    private $attributesProvider;

    /**
     * @param Provider $attributesProvider
     */
    public function __construct(
        Provider $attributesProvider
    ) {
        $this->attributesProvider = $attributesProvider;
    }

    /**
     * Copy from quote to order
     *
     * @param Quote $quote
     * @param Order $order
     * @return $this
     */
    public function fromQuoteToOrder($quote, $order)
    {
        $attributeCodes = $this->attributesProvider->getOrderAttributeCodes();
        foreach ($attributeCodes as $attributeCode) {
            $order->setData($attributeCode, $quote->getData($attributeCode));
        }
        $this->fromQuoteAddressToOrderAddress($quote, $order);
        return $this;
    }

    /**
     * Copy custom attributes from order address to customer address
     *
     * @param DataObject $orderAddress
     * @param DataObject $customerAddress
     */
    public function copyCustomAttributesFromOrderToCustomerAddress($orderAddress, $customerAddress)
    {
        $attributeCodes = $this->attributesProvider->getOrderAddressAttributeCodes();
        $customAttributes = $customerAddress->getData(CustomAttributesDataInterface::CUSTOM_ATTRIBUTES) ?? [];
        foreach ($attributeCodes as $attributeCode) {
            $customAttributes[$attributeCode] = $orderAddress->getData($attributeCode);
        }

        $customerAddress->setData(CustomAttributesDataInterface::CUSTOM_ATTRIBUTES, $customAttributes);
    }

    /**
     * @param Quote $quote
     * @param Order $order
     * @return $this
     */
    private function fromQuoteAddressToOrderAddress($quote, $order)
    {
        $attributeCodes = $this->attributesProvider->getOrderAddressAttributeCodes();
        $quoteShipping = $quote->getShippingAddress();
        $quoteBilling = $quote->getBillingAddress();
        $orderShipping = $order->getShippingAddress();
        $orderBilling = $order->getBillingAddress();

        foreach ($attributeCodes as $attributeCode) {
            $orderBilling->setData($attributeCode, $quoteBilling->getData($attributeCode));
            if ($orderShipping && $quoteShipping) {
                $orderShipping->setData($attributeCode, $quoteShipping->getData($attributeCode));
            }
        }

        return $this;
    }

    /**
     * Copy custom attributes from order address to customer data address
     *
     * @param QuoteAddress $quoteAddress
     * @param CustomerAddress $customerAddress
     */
    public function copyCustomAttributesFromQuoteToCustomerDataAddress($quoteAddress, $customerAddress)
    {
        $attributeCodes = $this->attributesProvider->getOrderAddressAttributeCodes();
        foreach ($attributeCodes as $attributeCode) {
            $customerAddress->setCustomAttribute($attributeCode, $quoteAddress->getData($attributeCode));
        }
    }
}
