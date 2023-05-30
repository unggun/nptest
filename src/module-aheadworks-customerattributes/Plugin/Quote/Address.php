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
namespace Aheadworks\CustomerAttributes\Plugin\Quote;

use Magento\Quote\Api\Data\AddressInterface as QuoteAddress;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddress;
use Aheadworks\CustomerAttributes\Model\Attribute\SalesDataCopier;

/**
 * Class Address
 *
 * @package Aheadworks\CustomerAttributes\Plugin\Quote
 */
class Address
{
    /**
     * @var SalesDataCopier
     */
    private $salesDataCopier;

    /**
     * @param SalesDataCopier $salesDataCopier
     */
    public function __construct(
        SalesDataCopier $salesDataCopier
    ) {
        $this->salesDataCopier = $salesDataCopier;
    }

    /**
     * Copy custom attributes from quote address to customer address
     *
     * @param QuoteAddress $quoteAddress
     * @param CustomerAddress $customerAddress
     * @return CustomerAddress
     */
    public function afterExportCustomerAddress(QuoteAddress $quoteAddress, CustomerAddress $customerAddress)
    {
        $this->salesDataCopier->copyCustomAttributesFromQuoteToCustomerDataAddress($quoteAddress, $customerAddress);
        return $customerAddress;
    }
}
