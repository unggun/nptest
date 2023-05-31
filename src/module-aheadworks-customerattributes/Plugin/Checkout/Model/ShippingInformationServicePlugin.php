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
namespace Aheadworks\CustomerAttributes\Plugin\Checkout\Model;

use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\Address\CustomAttributesFormatter;

/**
 * Class ShippingInformationServicePlugin
 *
 * @package Aheadworks\CustomerAttributes\Plugin\Checkout\Model
 */
class ShippingInformationServicePlugin
{
    /**
     * @var CustomAttributesFormatter
     */
    private $customAttributesFormatter;

    /**
     * @param CustomAttributesFormatter $customAttributesFormatter
     */
    public function __construct(CustomAttributesFormatter $customAttributesFormatter)
    {
        $this->customAttributesFormatter = $customAttributesFormatter;
    }

    /**
     * Format address custom attributes
     *
     * @param ShippingInformationManagementInterface $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation($subject, $cartId, $addressInformation)
    {
        $shippingAddress = $addressInformation->getShippingAddress();
        $billingAddress = $addressInformation->getBillingAddress();

        if ($shippingAddress) {
            $this->customAttributesFormatter->format($shippingAddress);
        }

        if ($billingAddress) {
            $this->customAttributesFormatter->format($billingAddress);
        }
    }
}
