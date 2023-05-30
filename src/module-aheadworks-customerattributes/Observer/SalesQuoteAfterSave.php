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

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\QuotePersistor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Class SalesQuoteAfterSave
 * @package Aheadworks\CustomerAttributes\Observer
 */
class SalesQuoteAfterSave implements ObserverInterface
{
    /**
     * @var QuotePersistor
     */
    private $quotePersistor;

    /**
     * @param QuotePersistor $quotePersistor
     */
    public function __construct(
        QuotePersistor $quotePersistor
    ) {
        $this->quotePersistor = $quotePersistor;
    }

    /**
     * {@inheritDoc}
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $this->quotePersistor->save($quote);

        return $this;
    }
}
