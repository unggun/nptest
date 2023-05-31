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
namespace Aheadworks\CustomerAttributes\Observer;

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\QuoteAddressPersistor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesQuoteAddressAfterLoad
 * @package Aheadworks\CustomerAttributes\Observer
 */
class SalesQuoteAddressAfterLoad implements ObserverInterface
{
    /**
     * @var QuoteAddressPersistor
     */
    private $quoteAddressPersistor;

    /**
     * @param QuoteAddressPersistor $quoteAddressPersistor
     */
    public function __construct(
        QuoteAddressPersistor $quoteAddressPersistor
    ) {
        $this->quoteAddressPersistor = $quoteAddressPersistor;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Observer $observer)
    {
        $address = $observer->getEvent()->getQuoteAddress();
        $this->quoteAddressPersistor->load($address);

        return $this;
    }
}
