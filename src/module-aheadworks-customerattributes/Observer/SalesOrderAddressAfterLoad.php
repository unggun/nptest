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

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\OrderAddressPersistor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesOrderAddressAfterLoad
 * @package Aheadworks\CustomerAttributes\Observer
 */
class SalesOrderAddressAfterLoad implements ObserverInterface
{
    /**
     * @var OrderAddressPersistor
     */
    private $orderAddressPersistor;

    /**
     * @param OrderAddressPersistor $orderAddressPersistor
     */
    public function __construct(
        OrderAddressPersistor $orderAddressPersistor
    ) {
        $this->orderAddressPersistor = $orderAddressPersistor;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Observer $observer)
    {
        $address = $observer->getEvent()->getAddress();
        $this->orderAddressPersistor->load($address);

        return $this;
    }
}
