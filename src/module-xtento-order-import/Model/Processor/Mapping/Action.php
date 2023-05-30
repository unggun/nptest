<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-18T14:40:00+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Mapping/Action.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor\Mapping;


use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Xtento\OrderImport\Model\Import;
use Xtento\XtCore\Model\System\Config\Source\Order\AllStatuses;
use Xtento\XtCore\Model\System\Config\Source\Shipping\Carriers;

class Action extends AbstractMapping
{
    protected $importFields = null;
    protected $mappingType = 'action';
    protected $importActions = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Action constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param ManagerInterface $eventManager
     * @param Carriers $carriers
     * @param AllStatuses $orderStatuses
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ManagerInterface $eventManager,
        Carriers $carriers,
        AllStatuses $orderStatuses,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($objectManager, $eventManager, $carriers, $orderStatuses, $data);
        $this->registry = $registry;
    }

    /*
     * [
     * 'label'
     * 'disabled'
     * 'tooltip'
     * 'default_value_disabled'
     * 'default_values'
     * ]
     */
    public function getMappingFields()
    {
        if ($this->importActions !== null) {
            return $this->importActions;
        }

        $importActions = [];
        $entity = $this->registry->registry('orderimport_profile')->getEntity();

        if ($entity == Import::ENTITY_ORDER) {
            $importActions = [
                'order_settings' => [
                    'label' => __('-- Order Actions -- '),
                    'disabled' => true,
                    'tooltip' => '',
                ],
                'import_order' => [
                    'label' => __('Import and/or update order'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'send_new_order_email' => [
                    'label' => __('New Order: Send new order email after import'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'invoice_settings' => [
                    'label' => __('-- Invoice Actions -- '),
                    'disabled' => true,
                    'tooltip' => '',
                ],
                /*'import_invoice' => [
                    'label' => __('Import invoice from file'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],*/
                'invoice_create' => [
                    'label' => __('Create invoice for imported order automatically'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'invoice_send_email' => [
                    'label' => __('Send invoice email to customer'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'invoice_mark_paid' => [
                    'label' => __('Set invoice status to "Paid" (=Capture Offline)'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'shipment_settings' => [
                    'label' => __('-- Shipment Actions -- '),
                    'disabled' => true,
                    'tooltip' => '',
                ],
                'shipment_create' => [
                    'label' => __('Create shipment for imported order'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'shipment_send_email' => [
                    'label' => __('Send shipment email to customer'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                /*'creditmemo_settings' => [
                    'label' => __('-- Credit Memo Actions -- '),
                    'disabled' => true,
                    'tooltip' => '',
                ],
                'creditmemo_create' => [
                    'label' => __('Create credit memo for imported order'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
                'creditmemo_send_email' => [
                    'label' => __('Send credit memo email to customer'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],*/
                /*'order_status_settings' => [
                    'label' => __('-- Status Actions -- '),
                    'disabled' => true,
                    'tooltip' => '',
                ],
                'send_order_update_email' => [
                    'label' => __('Send order update email to customer (see help)'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => '',
                    'tooltip' => __(
                        'If enabled, the order update email will be sent to the customer. This is the same email that also gets sent if you add a comment to an order from the Orders view, so it makes sense especially if you add an order comment. Note, this only works if order update emails are enabled in System - Configuration.'
                    ),
                ],*/
            ];
        }
        if ($entity == Import::ENTITY_CUSTOMER) {
            $importActions = [
                'customer_settings' => [
                    'label' => __('-- Customer Actions -- '),
                    'disabled' => true,
                    'tooltip' => '',
                ],
                'import_customer' => [
                    'label' => __('Import and/or update customer'),
                    'default_values' => $this->getDefaultValues('yesno'),
                    'default_value' => 0,
                    'tooltip' => '',
                ],
            ];
        }

        // Custom event to add fields
        $this->eventManager->dispatch(
            'xtento_orderimport_mapping_get_actions',
            [
                'importActions' => &$importActions,
                'entity' => $entity
            ]
        );

        $this->importActions = $importActions;

        return $this->importActions;
    }

    public function getImportActions()
    {
        return [
            'order' => [
                'invoice' => [
                    'class' => '\Xtento\OrderImport\Model\Import\Action\Order\Invoice',
                    'method' => 'invoice'
                ],
                'shipment' => [
                    'class' => '\Xtento\OrderImport\Model\Import\Action\Order\Shipment',
                    'method' => 'ship'
                ],
                'status' => [
                    'class' => '\Xtento\OrderImport\Model\Import\Action\Order\Status',
                    'method' => 'update'
                ],
            ]
        ];
    }

    public function formatField($fieldName, $fieldValue)
    {
        if ($fieldName == 'qty') {
            if ($fieldValue[0] == '+') {
                $fieldValue = sprintf("%+.4f", $fieldValue);
            } else {
                $fieldValue = sprintf("%.4f", $fieldValue);
            }
        }
        if ($fieldName == 'product_identifier') {
            $fieldValue = trim($fieldValue);
        }
        return $fieldValue;
    }
}
