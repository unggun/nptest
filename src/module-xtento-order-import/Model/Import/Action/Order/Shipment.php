<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-19T15:02:23+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Action/Order/Shipment.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Action\Order;

use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Shipping\Model\ConfigFactory;
use Magento\Store\Model\ScopeInterface;
use Xtento\OrderImport\Model\Import\Action\AbstractAction;
use Xtento\OrderImport\Model\Processor\Mapping\Action\Configuration;
use Xtento\XtCore\Helper\Utils;

class Shipment extends AbstractAction
{
    protected $allCarriers = null;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var TransactionFactory
     */
    protected $dbTransactionFactory;

    /**
     * @var CollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ConfigFactory
     */
    protected $shippingConfigFactory;

    /**
     * @var Order\Email\Sender\ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Utils
     */
    protected $utilsHelper;

    /**
     * Shipment constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Configuration $actionConfiguration
     * @param ProductFactory $modelProductFactory
     * @param TrackFactory $shipmentTrackFactory
     * @param TransactionFactory $resourceModelTransactionFactory
     * @param CollectionFactory $shipmentCollection
     * @param ShipmentLoader $shipmentLoader
     * @param ShipmentFactory $shipmentFactory
     * @param Order\Email\Sender\ShipmentSender $shipmentSender
     * @param ShipmentRepositoryInterface $shipmentRepositoryInterface
     * @param ScopeConfigInterface $configScopeConfigInterface
     * @param ConfigFactory $modelConfigFactory
     * @param ObjectManagerInterface $objectManager
     * @param Utils $utilsHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Configuration $actionConfiguration,
        ProductFactory $modelProductFactory,
        TrackFactory $shipmentTrackFactory,
        TransactionFactory $resourceModelTransactionFactory,
        CollectionFactory $shipmentCollection,
        ShipmentLoader $shipmentLoader,
        ShipmentFactory $shipmentFactory,
        Order\Email\Sender\ShipmentSender $shipmentSender,
        ShipmentRepositoryInterface $shipmentRepositoryInterface,
        ScopeConfigInterface $configScopeConfigInterface,
        ConfigFactory $modelConfigFactory,
        ObjectManagerInterface $objectManager,
        Utils $utilsHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productFactory = $modelProductFactory;
        $this->trackFactory = $shipmentTrackFactory;
        $this->dbTransactionFactory = $resourceModelTransactionFactory;
        $this->shipmentCollectionFactory = $shipmentCollection;
        $this->shipmentLoader = $shipmentLoader;
        $this->shipmentSender = $shipmentSender;
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentRepository = $shipmentRepositoryInterface;
        $this->scopeConfig = $configScopeConfigInterface;
        $this->shippingConfigFactory = $modelConfigFactory;
        $this->objectManager = $objectManager;
        $this->utilsHelper = $utilsHelper;

        parent::__construct($context, $registry, $actionConfiguration, $resource, $resourceCollection, $data);
    }

    public function ship()
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $updateData = $this->getUpdateData();

        #var_dump($updateData); die();

        // Check if order is holded and unhold if should be shipped
        if ($order->canUnhold() && $this->getActionSettingByFieldBoolean('shipment_create', 'enabled')) {
            $order->unhold()->save();
            $this->addDebugMessage(
                __("Order '%1': Order was unholded so it can be shipped.", $order->getIncrementId())
            );
        }

        // Create Shipment
        if ($this->getActionSettingByFieldBoolean('shipment_create', 'enabled')) {
            $doShipOrder = true;
            if ($doShipOrder && $order->canShip()) {
                $items = [];
                foreach ($order->getAllItems() as $orderItem) {
                    $items[$orderItem->getId()] = $orderItem->getQtyToShip();
                }
                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                $shipment = $this->shipmentFactory->create($order, $items);

                /* @var $shipment Order\Shipment */
                if ($shipment && $doShipOrder) {
                    $this->setMsiSource($shipment, isset($updateData['source_code']) ? $updateData['source_code'] : false);
                    if (isset($updateData['order_status_history_comment']) && !empty($updateData['order_status_history_comment'])) {
                        $shipment->addComment(
                            $updateData['order_status_history_comment'],
                            false,
                            true
                        );
                        $shipment->setCustomerNote($updateData['order_status_history_comment']);
                    }
                    $shipment->register();
                    if ($this->getActionSettingByFieldBoolean('shipment_send_email', 'enabled')) {
                        $shipment->setCustomerNoteNotify(true);
                    } else {
                        // Mark as sent so async email sending does not re-send
                        $shipment->setEmailSent(true);
                    }
                    #if (isset($updateData['custom1']) && !empty($updateData['custom1'])) $shipment->addComment($updateData['custom1'], true);
                    $shipment->getOrder()->setIsInProcess(true);

                    $transactionSave = $this->dbTransactionFactory->create()
                        ->addObject($shipment)->addObject($shipment->getOrder());
                    $transactionSave->save();

                    $this->setHasUpdatedObject(true);

                    if ($this->getActionSettingByFieldBoolean('shipment_send_email', 'enabled')) {
                        $this->shipmentSender->send($shipment);
                        $this->addDebugMessage(
                            __(
                                "Order '%1' has been shipped and the customer has been notified.",
                                $order->getIncrementId()
                            )
                        );
                    } else {
                        $this->addDebugMessage(
                            __(
                                "Order '%1' has been shipped and the customer has NOT been notified.",
                                $order->getIncrementId()
                            )
                        );
                    }

                    $this->setHasUpdatedObject(true);

                    unset($shipment);
                }
            } else {
                $this->addDebugMessage(
                    __(
                        "Order '%1' has NOT been shipped. Already shipped or order status not allowing shipping.",
                        $order->getIncrementId()
                    )
                );
            }
        }

        return true;
    }

    /**
     * Set Magento 2.3 MSI source. Must use ObjectManager as otherwise code would not be compatible with Magento <2.3
     *
     * @param $shipment
     * @param bool $sourceCode
     *
     * @return $this
     */
    protected function setMsiSource($shipment, $sourceCode = false)
    {
        if (version_compare($this->utilsHelper->getMagentoVersion(), '2.3', '<') || !$this->utilsHelper->isExtensionInstalled('Magento_Inventory')) {
            return $this;
        }

        $shipmentExtension = $shipment->getExtensionAttributes();
        if (empty($shipmentExtension)) {
            $shipmentExtension = $this->objectManager->create('Magento\Sales\Api\Data\ShipmentExtensionFactory')->create();
        }
        if ($sourceCode === false) {
            // Get default source
            $sourceCode = $this->objectManager->create('Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface')->getCode();
        }
        $shipmentExtension->setSourceCode($sourceCode);
        $shipment->setExtensionAttributes($shipmentExtension);
    }
}