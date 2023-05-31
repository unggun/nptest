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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject;
use Aheadworks\CustomerAttributes\Model\Attribute\SalesDataCopier;

/**
 * Class CoreCopyFieldsetOrderAddressToCustomerAddress
 * @package Aheadworks\CustomerAttributes\Observer
 */
class CoreCopyFieldsetOrderAddressToCustomerAddress implements ObserverInterface
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
     * Convert customer attributes from order address to customer address
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $orderAddress = $observer->getEvent()->getSource();
        $customerAddress = $observer->getEvent()->getTarget();

        if ($orderAddress instanceof DataObject && $customerAddress instanceof DataObject) {
            $this->salesDataCopier->copyCustomAttributesFromOrderToCustomerAddress($orderAddress, $customerAddress);
        }

        return $this;
    }
}
