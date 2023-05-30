<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-05T13:49:22+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Action/Order/Status.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Action\Order;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\ConfigFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Xtento\OrderImport\Model\Import\Action\AbstractAction;
use Xtento\OrderImport\Model\Processor\Mapping\Action\Configuration;
use Xtento\XtCore\Model\System\Config\Source\Order\AllStatuses;

class Status extends AbstractAction
{
    /**
     * @var AllStatuses
     */
    protected $orderStatuses;

    /**
     * @var ConfigFactory
     */
    protected $orderConfigFactory;

    /**
     * @var OrderCommentSender
     */
    protected $orderCommentSender;

    /**
     * Status constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Configuration $actionConfiguration
     * @param AllStatuses $orderStatuses
     * @param ConfigFactory $orderConfigFactory
     * @param OrderCommentSender $orderCommentSender
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Configuration $actionConfiguration,
        AllStatuses $orderStatuses,
        ConfigFactory $orderConfigFactory,
        OrderCommentSender $orderCommentSender,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderStatuses = $orderStatuses;
        $this->orderConfigFactory = $orderConfigFactory;
        $this->orderCommentSender = $orderCommentSender;

        parent::__construct($context, $registry, $actionConfiguration, $resource, $resourceCollection, $data);
    }

    public function update()
    {
        /** @var $order \Magento\Sales\Model\Order */
        $order = $this->getOrder();
        $updateData = $this->getUpdateData();

        if (!isset($updateData['order_status_history_comment'])) {
            $updateData['order_status_history_comment'] = '';
        }

        if ($this->getActionSettingByFieldBoolean('send_order_update_email', 'enabled')) {
            //$order->sendOrderUpdateEmail(true, @$updateData['order_status_history_comment']);
            $this->orderCommentSender->send($order, true, $updateData['order_status_history_comment']);
            /*
             *             $this->state->emulateAreaCode(
                             \Magento\Framework\App\Area::AREA_FRONTEND,
                             [$this->orderCommentSender, 'send'],
                             [$order, true, @$updateData['order_status_history_comment']]
                         );
             */
            $this->addDebugMessage(__("Order '%1': Order update email dispatched.", $order->getIncrementId()));
            $this->setHasUpdatedObject(true);
        }

        return true;
    }
}