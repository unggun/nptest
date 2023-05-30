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
namespace Aheadworks\CustomerAttributes\Plugin\Sales\Model\Order;

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\OrderAddressPersistor;
use Magento\Sales\Model\Order\AddressRepository;
use Magento\Sales\Model\Order\Address;

/**
 * Class PluginAddressRepository
 */
class PluginAddressRepository
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
     * @param AddressRepository $subject
     * @param Address $address
     * @return Address $address
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function afterSave($subject, $address)
    {
        $this->orderAddressPersistor->save($address);

        return $address;
    }
}