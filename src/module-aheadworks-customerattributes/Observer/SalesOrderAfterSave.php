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

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\OrderPersistor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Class SalesOrderAfterSave
 * @package Aheadworks\CustomerAttributes\Observer
 */
class SalesOrderAfterSave implements ObserverInterface
{
    /**
     * @var OrderPersistor
     */
    private $orderPersistor;

    /**
     * @param OrderPersistor $orderPersistor
     */
    public function __construct(
        OrderPersistor $orderPersistor
    ) {
        $this->orderPersistor = $orderPersistor;
    }

    /**
     * {@inheritDoc}
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->orderPersistor->save($order);

        return $this;
    }
}
