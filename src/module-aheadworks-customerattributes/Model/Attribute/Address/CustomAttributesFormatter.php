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
namespace Aheadworks\CustomerAttributes\Model\Attribute\Address;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * Class CustomAttributesFormatter
 *
 * @package Aheadworks\CustomerAttributes\Model\Attribute\Address
 */
class CustomAttributesFormatter
{
    /**
     * Format address custom attributes
     *
     * @param AddressInterface $address
     */
    public function format(AddressInterface $address)
    {
        $customAttributes = $address->getCustomAttributes();
        if ($customAttributes) {
            foreach ($customAttributes as $attribute) {
                $attributeValue = $attribute->getValue();
                if ($attributeValue && is_array($attributeValue)) {
                    if (isset($attributeValue['value']) && $attributeValue['value'] !== null) {
                        $attribute->setValue($attributeValue['value']);
                    }
                }
            }
        }
    }
}
