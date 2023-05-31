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

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\Address\CustomAttributesFormatter;

/**
 * Class PaymentInformationServicePlugin
 *
 * @package Aheadworks\CustomerAttributes\Plugin\Checkout\Model
 */
class PaymentInformationServicePlugin
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
     * @param PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformation($subject, $cartId, $paymentMethod, $billingAddress = null)
    {
        if ($billingAddress != null) {
            $this->customAttributesFormatter->format($billingAddress);
        }
    }
}
