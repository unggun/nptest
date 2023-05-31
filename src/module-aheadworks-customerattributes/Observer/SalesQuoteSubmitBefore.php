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

use Aheadworks\CustomerAttributes\Model\Attribute\SalesDataCopier;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesQuoteSubmitBefore
 * @package Aheadworks\CustomerAttributes\Observer
 */
class SalesQuoteSubmitBefore implements ObserverInterface
{
    /**
     * SalesDataCopier
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
     * {@inheritDoc}
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $this->salesDataCopier->fromQuoteToOrder($quote, $order);
        
        return $this;
    }
}
