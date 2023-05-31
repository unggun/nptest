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
namespace Aheadworks\CustomerAttributes\ViewModel\Customer;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class EmailData
 *
 * @package Aheadworks\CustomerAttributes\ViewModel\Customer
 */
class EmailData implements ArgumentInterface
{
    /**
     * Retrieve attribute value
     *
     * @param DataObject|null $customer
     * @param string|null $attributeCode
     * @return string
     */
    public function getAttributeValue($customer, $attributeCode)
    {
        $value = '';

        if ($attributeCode && $customer instanceof DataObject) {
            foreach ((array)$customer->getData('custom_attributes') as $attributeData) {
                $attribute = is_object($attributeData)
                    ? $attributeData
                    : new DataObject($attributeData);

                if ($attribute->getAttributeCode() == $attributeCode) {
                    $value = (string)$attribute->getValue();
                }
            }
        }

        return $value;
    }
}
