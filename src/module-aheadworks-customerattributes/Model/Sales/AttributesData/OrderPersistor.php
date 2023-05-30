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
namespace Aheadworks\CustomerAttributes\Model\Sales\AttributesData;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Aheadworks\CustomerAttributes\Model\Sales\Order;
use Aheadworks\CustomerAttributes\Model\Sales\OrderFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order as OrderResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class OrderPersistor
 * @package Aheadworks\CustomerAttributes\Model\Sales\AttributesData
 */
class OrderPersistor
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @param OrderFactory $orderFactory
     * @param OrderResource $orderResource
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderResource $orderResource
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderResource = $orderResource;
    }

    /**
     * Save
     *
     * @param AbstractModel $order
     * @throws AlreadyExistsException
     */
    public function save(AbstractModel $order)
    {
        /** @var Order $orderAttributeModel */
        $orderAttributeModel = $this->orderFactory->create();
        $data = $order->getData();
        $data[Attribute::ORDER_ID] = $order->getId();
        $orderAttributeModel->addData($data);

        $this->orderResource->save($orderAttributeModel);
    }

    /**
     * Load
     *
     * @param AbstractModel $order
     */
    public function load(AbstractModel $order)
    {
        /** @var Order $orderAttributeModel */
        $orderAttributeModel = $this->orderFactory->create();
        $this->orderResource->load($orderAttributeModel, $order->getId());
        $order->addData($orderAttributeModel->getData());
    }
}
