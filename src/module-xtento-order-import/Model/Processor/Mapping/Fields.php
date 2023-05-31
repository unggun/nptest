<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-11-14T10:52:09+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Mapping/Fields.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor\Mapping;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Xtento\OrderImport\Helper\Entity;
use Xtento\OrderImport\Model\Import;
use Xtento\XtCore\Model\System\Config\Source\Order\AllStatuses;
use Xtento\XtCore\Model\System\Config\Source\Shipping\Carriers;
use Magento\Customer\Helper\Address as AddressHelper;

class Fields extends AbstractMapping
{
    protected $importFields = null;
    protected $mappingType = 'fields';

    /**
     * @var Entity
     */
    protected $entityHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Shipping\Model\ConfigFactory
     */
    protected $shippingConfigFactory;

    /**
     * @var \Magento\Payment\Model\ConfigFactory
     */
    protected $paymentConfigFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $eavAttributeCollectionFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AddressHelper
     */
    protected $addressHelper;

    /**
     * Fields constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param ManagerInterface $eventManager
     * @param Carriers $carriers
     * @param AllStatuses $orderStatuses
     * @param Entity $entityHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Shipping\Model\ConfigFactory $shippingConfigFactory
     * @param \Magento\Payment\Model\ConfigFactory $paymentConfigFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavAttributeCollectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $customerAddressFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param AddressHelper $addressHelper
     * @param array $data
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ManagerInterface $eventManager,
        Carriers $carriers,
        AllStatuses $orderStatuses,
        Entity $entityHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Shipping\Model\ConfigFactory $shippingConfigFactory,
        \Magento\Payment\Model\ConfigFactory $paymentConfigFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavAttributeCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        AddressHelper $addressHelper,
        array $data = []
    ) {
        parent::__construct($objectManager, $eventManager, $carriers, $orderStatuses, $data);
        $this->entityHelper = $entityHelper;
        $this->registry = $registry;
        $this->shippingConfigFactory = $shippingConfigFactory;
        $this->paymentConfigFactory = $paymentConfigFactory;
        $this->orderConfig = $orderConfig;
        $this->eavAttributeCollectionFactory = $eavAttributeCollectionFactory;
        $this->customerFactory = $customerFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->storeManager = $storeManager;
        $this->addressHelper = $addressHelper;
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
        if ($this->importFields !== null) {
            return $this->importFields;
        }

        $entity = $this->registry->registry('orderimport_profile')->getEntity();

        $importFields = [];

        if ($entity == Import::ENTITY_ORDER) {
            // Get fields for "sales_order" table
            $table = "order";

            $importFields[$table . '!info'] = [
                'label' => __('Order (sales_order)'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            $resource = $this->registry->registry('orderimport_profile')->getResource();
            $columns = array_keys($resource->getConnection()->describeTable($resource->getTable('sales_' . $table)));
            $columns = array_merge($columns, ['gift_message', 'gift_message_from', 'gift_message_to', 'customer_password']); // Custom fields
            asort($columns);
            $ignoredColumns = ['entity_id', 'quote_id', 'gift_message_id', 'applied_rule_ids', 'billing_address_id', 'quote_address_id', 'relation_child_id', 'relation_child_real_id', 'relation_parent_id', 'relation_parent_real_id', 'shipping_address_id'];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName
                ];
                if ($columnName == 'shipping_method') {
                    $shippingMethods = [];
                    foreach ($this->shippingConfigFactory->create()->getAllCarriers() as $shippingCode => $shippingModel) {
                        try {
                            $methodTitle = $shippingModel->getConfigData('title');
                            if (empty($methodTitle)) {
                                continue;
                            }
                            $carrierMethods = $shippingModel->getAllowedMethods();
                        } catch (\Error $e) {
                            continue;
                        } catch (\Exception $e) {
                            continue;
                        }
                        if (!$carrierMethods) {
                            continue;
                        }
                        foreach ($carrierMethods as $methodCode => $methodTitle) {
                            $shippingMethods[$shippingCode . '_' . $methodCode] = '[' . $shippingCode . '] ' . $methodTitle;
                        }
                    }
                    $importFields[$table . "|" . $columnName]['default_values'] = $shippingMethods;
                }
                if ($columnName == 'status') {
                    $orderStatuses = [];
                    foreach ($this->orderConfig->getStatuses() as $orderStatus => $label) {
                        $orderStatuses[$orderStatus] = $label;
                    }
                    $importFields[$table . "|" . $columnName]['default_values'] = $orderStatuses;
                }
                if ($columnName == 'state') {
                    $orderStates = [];
                    foreach ($this->orderConfig->getStates() as $orderState => $label) {
                        $orderStates[$orderState] = $label;
                    }
                    $importFields[$table . "|" . $columnName]['default_values'] = $orderStates;
                }
                if ($columnName == 'store_id') {
                    $stores = [];
                    foreach ($this->storeManager->getStores(true) as $store) {
                        $stores[$store->getId()] = sprintf('%s (ID: %d)', $store->getName(), $store->getId());
                    }
                    $importFields[$table . "|" . $columnName]['default_values'] = $stores;

                    // Add store_code
                    $columnName = 'store_code';
                    $importFields[$table . "|" . $columnName] = [
                        'label' => $columnName
                    ];
                }
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];

            // Billing address fields
            $table = "order_address_billing";

            $importFields[$table . '!info'] = [
                'label' => __('Billing Address (sales_order_address)'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            $columns = array_keys($resource->getConnection()->describeTable($resource->getTable('sales_order_address')));
            asort($columns);
            $ignoredColumns = ['entity_id', 'address_type', 'parent_id', 'address_quote_id', 'customer_id', 'quote_address_id'];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                if ($columnName == 'street') {
                    for ($i = 1; $i <= $this->addressHelper->getStreetLines(); $i++) {
                        $importFields[$table . "|" . $columnName . $i] = [
                            'label' => $columnName . $i
                        ];
                    }
                    continue;
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName
                ];
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];

            // Shipping address fields
            $table = "order_address_shipping";

            $importFields[$table . '!info'] = [
                'label' => __('Shipping Address (sales_order_address)'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                if ($columnName == 'street') {
                    for ($i = 1; $i <= $this->addressHelper->getStreetLines(); $i++) {
                        $importFields[$table . "|" . $columnName . $i] = [
                            'label' => $columnName . $i
                        ];
                    }
                    continue;
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName
                ];
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];

            // Payment fields
            $table = "order_payment";

            $importFields[$table . '!info'] = [
                'label' => __('Payment Fields (sales_order_payment)'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            $columns = array_keys($resource->getConnection()->describeTable($resource->getTable('sales_' . $table)));
            asort($columns);
            $ignoredColumns = ['entity_id', 'parent_id', 'quote_payment_id'];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                $importFields[$table . "|" . $columnName] = array(
                    'label' => $columnName
                );
                if ($columnName == 'method') {
                    $paymentMethods = [];
                    foreach ($this->paymentConfigFactory->create()->getActiveMethods() as $paymentCode => $paymentMethod) {
                        $paymentMethods[$paymentCode] = $paymentMethod->getTitle();
                    }
                    $importFields[$table . "|" . $columnName]['default_values'] = $paymentMethods;
                }
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];

            // Payment transaction fields
            $table = "payment_transaction";

            $importFields[$table . '!info'] = [
                'label' => __('Payment Transaction (sales_payment_transaction)'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            $columns = array_keys($resource->getConnection()->describeTable($resource->getTable('sales_' . $table)));
            asort($columns);
            $ignoredColumns = ['transaction_id', 'parent_id', 'order_id', 'payment_id'];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName,
                    'group' => 'payment_transactions'
                ];
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];

            // Item fields
            $table = "order_item";

            $importFields[$table . '!info'] = [
                'label' => __('Item Fields (sales_order_item)'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            $columns = array_keys($resource->getConnection()->describeTable($resource->getTable('sales_' . $table)));
            $columns = array_merge($columns, ['gift_message', 'gift_message_from', 'gift_message_to']); // Custom fields
            asort($columns);
            $ignoredColumns = ['order_id', 'quote_item_id', 'store_id', 'gift_message_id', 'applied_rule_ids'];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName,
                    'group' => 'items'
                ];
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];
        }
        if ($entity == Import::ENTITY_CUSTOMER) {
            $table = 'customer';
            $importFields[$table . '!info'] = [
                'label' => __('Customer'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $eavAttributeCollection */
            $eavAttributeCollection = $this->eavAttributeCollectionFactory->create();
            $eavAttributeCollection->setEntityTypeFilter($this->customerFactory->create()->getResource()->getTypeId());
            $columns = array_values($eavAttributeCollection->getColumnValues('attribute_code'));
            $columns = array_merge($columns, ['password_unencrypted', 'id']); // Custom fields
            asort($columns);
            $ignoredColumns = [];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
                $attribute = $eavAttributeCollection->getItemByColumnValue('attribute_code', $columnName);
                $required = '';
                if ($attribute && $attribute->getIsRequired()) {
                    $required = __(' (Required)');
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName . $required
                ];
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];

            // Customer address
            $table = 'customer_address';
            $importFields[$table . '!info'] = [
                'label' => __('Customer Address'),
                'disabled' => true,
                'tooltip' => __(''),
                'start_optgroup' => true,
            ];

            /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $eavAttributeCollection */
            $eavAttributeCollection = $this->eavAttributeCollectionFactory->create();
            $eavAttributeCollection->setEntityTypeFilter($this->customerAddressFactory->create()->getResource()->getTypeId());
            $columns = array_values($eavAttributeCollection->getColumnValues('attribute_code'));
            $columns = array_merge($columns, ['is_primary_billing_address', 'is_primary_shipping_address', 'street1', 'street2', 'street3']); // Custom fields
            asort($columns);
            $ignoredColumns = ['street'];
            foreach ($columns as $columnId => $columnName) {
                if (in_array($columnName, $ignoredColumns)) {
                    continue;
                }
                /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
                $attribute = $eavAttributeCollection->getItemByColumnValue('attribute_code', $columnName);
                $required = '';
                if ($attribute && $attribute->getIsRequired()) {
                    $required = __(' (Required)');
                }
                $importFields[$table . "|" . $columnName] = [
                    'label' => $columnName . $required,
                    'group' => 'address'
                ];
                if ($columnName == 'is_primary_billing_address' || $columnName == 'is_primary_shipping_address') {
                    $importFields[$table . "|" . $columnName]['default_values'] = $this->getDefaultValues('yesno');
                }
            }

            $importFields[$table . '!stop_optgroup'] = [
                'label' => '',
                'stop_optgroup' => true
            ];
        }

        // Custom event to add fields
        $additional = new \Magento\Framework\DataObject();
        $this->eventManager->dispatch('xtento_orderimport_mapping_get_fields', ['class' => $this, 'entity' => $entity, 'additional' => $additional]);
        $additionalFields = $additional->getFields();
        if ($additionalFields) {
            $importFields = array_merge_recursive($importFields, $additionalFields);
        }

        // Feature: merge fields from custom/fields.php so custom fields can be added
        $this->importFields = $importFields;
        return $this->importFields;
    }

    public function formatField($fieldName, $fieldValue)
    {
        if ($fieldName == 'order_identifier') {
            $fieldValue = trim($fieldValue);
        }
        return $fieldValue;
    }
}
