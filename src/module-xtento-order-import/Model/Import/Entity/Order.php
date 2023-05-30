<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-10-22T15:23:07+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Entity/Order.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Entity;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\InvoiceFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Xtento\OrderImport\Helper\Entity;
use Xtento\OrderImport\Model\History;
use Xtento\OrderImport\Model\HistoryFactory;
use Xtento\OrderImport\Model\Import;
use Xtento\OrderImport\Model\Log;
use Xtento\OrderImport\Model\Processor\Mapping\ActionFactory;
use Xtento\OrderImport\Model\ProfileFactory;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Quote\Model\Quote\TotalsCollector;
use \Magento\Quote\Model\Cart\ShippingMethodConverter;

class Order extends AbstractEntity
{
    protected $calculatedTaxRatesByType = [];

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var InvoiceFactory
     */
    protected $invoiceFactory;

    /**
     * @var CollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var ActionFactory
     */
    protected $mappingActionFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Entity
     */
    protected $entityHelper;

    /**
     * @var HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var Import\Processors
     */
    protected $importProcessors;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var OrderManagement
     */
    protected $orderManagement;

    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var ShippingMethodConverter
     */
    protected $shippingMethodConverter;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Sales\Model\Order\TaxFactory
     */
    protected $orderTaxFactory;

    /**
     * @var \Magento\Sales\Model\Order\Tax\ItemFactory
     */
    protected $orderTaxItemFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $dbConnection;

    /**
     * Order constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param Registry $frameworkRegistry
     * @param OrderFactory $modelOrderFactory
     * @param InvoiceFactory $orderInvoiceFactory
     * @param CollectionFactory $orderShipmentCollectionFactory
     * @param CreditmemoFactory $orderCreditmemoFactory
     * @param ProfileFactory $modelProfileFactory
     * @param ManagerInterface $eventManagerInterface
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param ActionFactory $mappingActionFactory
     * @param ObjectManagerInterface $objectManager
     * @param Entity $entityHelper
     * @param HistoryFactory $historyFactory
     * @param Import\Processors $importProcessors
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param QuoteFactory $quoteFactory
     * @param OrderManagement $orderManagement
     * @param TotalsCollector $totalsCollector
     * @param ShippingMethodConverter $shippingMethodConverter
     * @param \Magento\Tax\Model\Calculation $taxCalculation
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Sales\Model\Order\TaxFactory $orderTaxFactory
     * @param \Magento\Sales\Model\Order\Tax\ItemFactory $orderTaxItemFactory
     * @param array $data
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Registry $frameworkRegistry,
        OrderFactory $modelOrderFactory,
        InvoiceFactory $orderInvoiceFactory,
        CollectionFactory $orderShipmentCollectionFactory,
        CreditmemoFactory $orderCreditmemoFactory,
        ProfileFactory $modelProfileFactory,
        ManagerInterface $eventManagerInterface,
        \Magento\Store\Model\App\Emulation $appEmulation,
        ActionFactory $mappingActionFactory,
        ObjectManagerInterface $objectManager,
        Entity $entityHelper,
        HistoryFactory $historyFactory,
        Import\Processors $importProcessors,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Config $orderConfig,
        QuoteFactory $quoteFactory,
        OrderManagement $orderManagement,
        TotalsCollector $totalsCollector,
        ShippingMethodConverter $shippingMethodConverter,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Sales\Model\Order\TaxFactory $orderTaxFactory,
        \Magento\Sales\Model\Order\Tax\ItemFactory $orderTaxItemFactory,
        array $data = []
    ) {
        $this->orderFactory = $modelOrderFactory;
        $this->invoiceFactory = $orderInvoiceFactory;
        $this->shipmentCollectionFactory = $orderShipmentCollectionFactory;
        $this->creditmemoFactory = $orderCreditmemoFactory;
        $this->profileFactory = $modelProfileFactory;
        $this->eventManager = $eventManagerInterface;
        $this->appEmulation = $appEmulation;
        $this->mappingActionFactory = $mappingActionFactory;
        $this->objectManager = $objectManager;
        $this->entityHelper = $entityHelper;
        $this->historyFactory = $historyFactory;
        $this->importProcessors = $importProcessors;
        $this->orderSender = $orderSender;
        $this->orderConfig = $orderConfig;
        $this->quoteFactory = $quoteFactory;
        $this->orderManagement = $orderManagement;
        $this->totalsCollector = $totalsCollector;
        $this->shippingMethodConverter = $shippingMethodConverter;
        $this->taxCalculation = $taxCalculation;
        $this->taxConfig = $taxConfig;
        $this->orderTaxFactory = $orderTaxFactory;
        $this->orderTaxItemFactory = $orderTaxItemFactory;
        $this->dbConnection = $resourceConnection->getConnection();
        parent::__construct($resourceConnection, $frameworkRegistry, $data);
    }

    /**
     * Prepare import
     *
     * @param $updatesInFilesToProcess
     *
     * @return bool
     */
    public function prepareImport($updatesInFilesToProcess)
    {
        // Prepare actions to apply
        $actions = $this->getActions();
        $actionFields = $this->getActionFields();
        foreach ($actions as &$action) {
            $actionField = $action['field'];
            if (isset($actionFields[$actionField])) {
                $action['field_data'] = $actionFields[$actionField];
            } else {
                unset($action);
            }
        }
        $this->setActions($actions);
        return true;
    }

    protected function loadOrder($orderData)
    {
        $order = $this->orderFactory->create();

        // Identify order and return $order
        $orderIdentifier = $this->getConfig('order_identifier');
        if ($orderIdentifier === 'order_increment_id') {
            if (isset($orderData['increment_id'])) {
                $order = $this->orderFactory->create()->loadByIncrementId(strval($orderData['increment_id']));
            } else {
                return ['object' => $order, 'warning' => 'Could not identify order using increment_id as increment_id field is not mapped.'];
            }
        }
        if ($orderIdentifier === 'order_ext_order_id') {
            if (isset($orderData['ext_order_id'])) {
                $order = $this->orderFactory->create()->loadByAttribute('ext_order_id', strval($orderData['ext_order_id']));
            } else {
                return ['object' => $order, 'warning' => 'Could not identify order using ext_order_id as ext_order_id field is not mapped.'];
            }
        }
        if ($orderIdentifier === 'order_entity_id') {
            if (isset($orderData['entity_id'])) {
                $order = $this->orderFactory->create()->load($orderData['entity_id']);
            } else {
                return ['object' => $order, 'warning' => 'Could not identify order using entity_id as entity_id field is not mapped.'];
            }
        }
        if ($orderIdentifier === 'invoice_increment_id') {
            if (isset($orderData['increment_id'])) {
                $invoice = $this->invoiceFactory->create()->loadByIncrementId(strval($orderData['increment_id']));
                if ($invoice->getId()) {
                    $order = $invoice->getOrder();
                }
            } else {
                return ['object' => $order, 'warning' => 'Could not identify order using invoice increment_id as increment_id field is not mapped.'];
            }
        }
        if ($orderIdentifier === 'shipment_increment_id') {
            if (isset($orderData['increment_id'])) {
                $shipment = $this->shipmentCollectionFactory->create()->addAttributeToFilter(
                    'increment_id',
                    strval($orderData['increment_id'])
                )->getFirstItem();
                if ($shipment->getId()) {
                    $order = $shipment->getOrder();
                }
            } else {
                return ['object' => $order, 'warning' => 'Could not identify order using shipment increment_id as increment_id field is not mapped.'];
            }
        }
        if ($orderIdentifier === 'creditmemo_increment_id') {
            if (isset($orderData['increment_id'])) {
                $creditmemo = $this->creditmemoFactory->create()
                    ->getCollection()
                    ->addAttributeToFilter('increment_id', strval($orderData['increment_id']))
                    ->getFirstItem();
                if ($creditmemo->getId()) {
                    $order = $creditmemo->getOrder();
                }
            } else {
                return ['object' => $order, 'warning' => 'Could not identify order using credit memo increment_id as increment_id field is not mapped.'];
            }
        }

        return $order;
    }

    /**
     * @param $rowIdentifier
     * @param $updateData
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($rowIdentifier, $updateData)
    {
        #var_dump($updateData); die();
        if (!isset($updateData['order'])) {
            //@todo: remove isset(..[order] below
            $importDebugMessages[] = __("No order data found, not starting order processors.");
            $importChanged = false;
            return $this->returnDebugResult($importChanged, $importDebugMessages);
        }

        if (!$this->getTestMode()) {
            $historyEntry = $this->historyFactory->create();
            $historyEntry->setProfileId($this->getProfile()->getId());
            $historyEntry->setLogId($this->registry->registry('orderimport_log')->getId());
            $historyEntry->setEntity(Import::ENTITY_ORDER);
        } else {
            $historyEntry = false;
        }

        $objectId = 'not set';
        if (isset($updateData['order']) && isset($updateData['order']['increment_id'])) {
            $objectId = $updateData['order']['increment_id'];
        }
        if (isset($updateData['order']) && isset($updateData['order']['entity_id'])) {
            $objectId = $updateData['order']['entity_id'];
        }

        // Result (and debug information) returned
        $importChanged = false;
        $importDebugMessages = [];
        $importMode = $this->getConfig('import_mode');

        // Prepare updateData
        $updateData['__importMode'] = $importMode;
        $updateData['__rowIdentifier'] = $rowIdentifier;

        // Check if object already exists
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create();
        if (isset($updateData['order'])) {
            $updateData['order']['__isObjectNew'] = true;
            $order = $this->loadOrder($updateData['order']);
            if (is_array($order) && isset($order['warning'])) {
                $importDebugMessages[] = __("Row %1 (ID: %2): Order identification not possible: %3", $rowIdentifier, $objectId, $order['warning']);
                $order = $order['object'];
            }
            if ($order && $order->getId()) {
                $updateData['order']['__isObjectNew'] = false;
            }
        }

        // Import mode validations
        if ($importMode == Import::IMPORT_MODE_NEW && $updateData['order']['__isObjectNew'] === false) {
            $importDebugMessages[] = __("Row %1 (ID: %2): Import mode is 'New only', but order exists already. Skipping.", $rowIdentifier, $objectId);
            return $this->returnDebugResult(false, $importDebugMessages);
        }
        if ($importMode == Import::IMPORT_MODE_DELETE && $updateData['order']['__isObjectNew'] === true) {
            $importDebugMessages[] = __("Row %1 (ID: %2): Import mode is 'Delete', but order does not exist in Magento. Skipping.", $rowIdentifier, $objectId);
            return $this->returnDebugResult($importChanged, $importDebugMessages);
        }
        if ($importMode == Import::IMPORT_MODE_UPDATE && $updateData['order']['__isObjectNew'] === true) {
            $importDebugMessages[] = __("Row %1 (ID: %2): Import mode is 'Update, Skip new', but order does not exist in Magento. Skipping.", $rowIdentifier, $objectId);
            return $this->returnDebugResult($importChanged, $importDebugMessages);
        }

        // Register update data for third party processing
        $this->registry->unregister('xtento_orderimport_updatedata');
        $this->registry->register('xtento_orderimport_updatedata', $updateData);

        if ($this->shouldImportOrders()) {
            // Import/update validations
            $stopProcessing = false;
            $validationResults = [];

            $importProcessorClasses = $this->importProcessors->getProcessorClasses($this->getProfile()->getEntity());
            foreach ($importProcessorClasses as $importProcessorClass) {
                $importProcessor = $this->objectManager->get($importProcessorClass);
                $importProcessor->setProfile($this->getProfile());
                // Validate current processor
                $importProcessorValidationResult = $importProcessor->validate($updateData);
                if ($importProcessorValidationResult !== true) {
                    if (array_key_exists('stop', $importProcessorValidationResult) && $importProcessorValidationResult['stop'] === true) {
                        $importProcessorValidationResult['message'] .= __(" [Breaking error, stops import]");
                        $stopProcessing = true;
                        $importDebugMessages[] = __("Row %1 (ID: %2): Validation error: %3", $rowIdentifier, $objectId, $importProcessorValidationResult['message']);
                    } else {
                        $importDebugMessages[] = __("Row %1 (ID: %2): Validation notice: %3", $rowIdentifier, $objectId, $importProcessorValidationResult['message']);
                    }
                    if (array_key_exists('skip_processor', $importProcessorValidationResult) && $importProcessorValidationResult['skip_processor'] === true) {
                        continue;
                    }
                }
                $validationResults[$importProcessorClass] = $importProcessorValidationResult;
                // Validate sub processors
                foreach ($importProcessor->getSubProcessorClasses() as $subProcessorClass) {
                    $subProcessor = $this->objectManager->get($subProcessorClass);
                    $subProcessorValidationResult = $subProcessor->validate($updateData);
                    if ($subProcessorValidationResult !== true) {
                        if (array_key_exists('stop', $subProcessorValidationResult) && $subProcessorValidationResult['stop'] === true) {
                            $subProcessorValidationResult['message'] .= __(" [Breaking error, stops import]");
                            $stopProcessing = true;
                            $importDebugMessages[] = __("Row %1 (ID: %2): Validation error: %3", $rowIdentifier, $objectId, $subProcessorValidationResult['message']);
                        } else {
                            $importDebugMessages[] = __("Row %1 (ID: %2): Validation notice: %3", $rowIdentifier, $objectId, $subProcessorValidationResult['message']);
                        }
                    }
                    $validationResults[$subProcessorClass] = $subProcessorValidationResult;
                }
            }

            // Test mode - stop import
            if ($this->getTestMode()) {
                if ($stopProcessing) {
                    $importDebugMessages[] = __("Row %1 (ID: %2): FAILED validation. Potential errors can be seen above. Stopping.", $rowIdentifier, $objectId);
                }
                $importDebugMessages[] = __("Row %1 (ID: %2): Finished validation. Stopping, as this is test mode.", $rowIdentifier, $objectId);
                return $this->returnDebugResult(true, $importDebugMessages);
            } else {
                if ($stopProcessing) {
                    // History log
                    $historyEntry->setEntityId($order->getId());
                    $historyEntry->setIncrementId($order->getIncrementId());
                    $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                    $historyEntry->setImportedAt(time());
                    $historyEntry->setImportData(json_encode($updateData));
                    $historyEntry->setStatus(History::RESULT_FAILED);
                    $historyEntry->setLog(__('Order FAILED validation, stopping: %1', implode("\n", $importDebugMessages)));
                    if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                    // End History Log
                    $importDebugMessages[] = __("Row %1 (ID: %2): FAILED validation. Potential errors can be seen above. Stopping.", $rowIdentifier, $objectId);
                    return $this->returnDebugResult(true, $importDebugMessages);
                }
                $importDebugMessages[] = __("Row %1 (ID: %2): Ready to process.", $rowIdentifier, $objectId);
            }
        } else {
            $importDebugMessages[] = __("Row %1 (ID: %2): \"Import Orders\" action in \"Actions\" tab not enabled, not running order import procedure.", $rowIdentifier, $objectId);
        }

        // Run "Import order only if..." filters on existing orders
        // Get validation profile to see if order should be processed
        /* Alternative approach if conditions check fails, we've seen this happening in a 1.5.0.1 installation, the profile conditions were simply empty and the profile needed to be loaded again: */
        $validationProfile = $this->getProfile();
        if ($updateData['order']['__isObjectNew'] === false) {
            // Get validation profile to see if order should be processed
            $validationProfile = $this->getProfile();
            $importConditions = $validationProfile->getData('conditions_serialized');
            if (strlen($importConditions) > 90) {
                // Force load profile for rule validation, as it fails on some stores if the profile is not re-loaded
                $validationProfile = $this->profileFactory->create()->load($this->getProfile()->getId());
            }
            // Check if order should be imported, matched by the "Settings & Filters" "Process order only if..." settings
            $collectionItemValidated = true;
    
            // Custom validation event
            $this->eventManager->dispatch(
                'xtento_orderimport_custom_validation',
                [
                    'validationProfile' => $validationProfile,
                    'collectionItem' => $order,
                    'collectionItemValidated' => &$collectionItemValidated,
                ]
            );
    
            // If not validated, skip object
            if (!($collectionItemValidated && $validationProfile->validate($order))) {
                $importDebugMessages[] = __("Order '%1' did not match import profile filters and will be skipped.", $order->getIncrementId());
                // History log
                $historyEntry->setEntityId($order->getId());
                $historyEntry->setIncrementId($order->getIncrementId());
                $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                $historyEntry->setImportedAt(time());
                $historyEntry->setImportData(json_encode($updateData));
                $historyEntry->setStatus(History::RESULT_WARNING);
                $historyEntry->setLog(__('Order skipped: %1', implode("\n", $importDebugMessages)));
                if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                // End History Log
                $importChanged = false;
                unset($order);
                return $this->returnDebugResult($importChanged, $importDebugMessages);
            }
        }

        $this->eventManager->dispatch(
            'xtento_orderimport_process_order_before',
            [
                'import_profile' => $validationProfile,
                'update_data' => &$updateData,
                'order' => $order
            ]
        );


        // Emulate correct store
        if ($order->getStoreId()) {
            $this->appEmulation->startEnvironmentEmulation($order->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        // Run import
        #var_dump($validationResults);

        if ($importMode == Import::IMPORT_MODE_DELETE) {
            $order->delete();
            // History log
            $historyEntry->setEntityId($order->getId());
            $historyEntry->setIncrementId($order->getIncrementId());
            $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
            $historyEntry->setImportedAt(time());
            $historyEntry->setLog(__('Order has been deleted.'));
            $historyEntry->setStatus(History::RESULT_SUCCESSFUL);
            $historyEntry->setImportData(json_encode($updateData));
            if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
            // End History Log
            $importDebugMessages[] = __("Row %1 (ID: %2): Order %3 has been deleted.", $rowIdentifier, $order->getIncrementId(), $objectId);
            return $this->returnDebugResult(true, $importDebugMessages);
        } else if ($this->shouldImportOrders()) {
            // Process / update order
            foreach ($importProcessorClasses as $importProcessorClass) {
                $importProcessor = $this->objectManager->get($importProcessorClass);
                try {
                    $this->dbConnection->beginTransaction();
                    $importProcessorResult = $importProcessor->setProfile($this->getProfie())->process($order, $updateData);
                    $this->dbConnection->commit();
                } catch (\Exception $e) {
                    $this->dbConnection->rollBack();
                    $reflection = new \ReflectionClass($this->dbConnection);
                    if ($reflection->hasProperty('_isRolledBack')) {
                        $reflectionProperty = $reflection->getProperty('_isRolledBack');
                        $reflectionProperty->setAccessible(true);
                        $reflectionProperty->setValue($this->dbConnection, false);
                    }
                    if ($reflection->hasProperty('_transactionLevel')) {
                        $reflectionProperty = $reflection->getProperty('_transactionLevel');
                        $reflectionProperty->setAccessible(true);
                        $reflectionProperty->setValue($this->dbConnection, 0);
                    }
                    $importDebugMessages[] = __("Row %1 (ID: %2): Exception (Processor: %3): %4", $rowIdentifier, $objectId, $importProcessorClass, $e->getMessage());
                    $historyEntry->setEntityId($order->getId());
                    $historyEntry->setIncrementId($order->getIncrementId());
                    $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                    $historyEntry->setImportedAt(time());
                    $historyEntry->setImportData(json_encode($updateData));
                    $historyEntry->setStatus(History::RESULT_FAILED);
                    $historyEntry->setLog(implode("\n", $importDebugMessages));
                    if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                    throw $e;
                }
                if ($importProcessorResult !== true) {
                    $stopProcessing = false;
                    if (array_key_exists('stop', $importProcessorResult) && $importProcessorResult['stop'] === true) {
                        $stopProcessing = true;
                        $importProcessorResult['message'] .= __(" [Breaking error, stopped import]");
                        $importDebugMessages[] = __("Row %1 (ID: %2): Processing error: %3", $rowIdentifier, $objectId, $importProcessorResult['message']);
                    } else {
                        $importDebugMessages[] = __("Row %1 (ID: %2): Processing notice: %3", $rowIdentifier, $objectId, $importProcessorResult['message']);
                    }
                    if ($stopProcessing) {
                        // History log
                        $historyEntry->setEntityId($order->getId());
                        $historyEntry->setIncrementId($order->getIncrementId());
                        $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                        $historyEntry->setImportedAt(time());
                        $historyEntry->setImportData(json_encode($updateData));
                        $historyEntry->setStatus(History::RESULT_FAILED);
                        $historyEntry->setLog(implode("\n", $importDebugMessages));
                        if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                        // End History Log
                        return $this->returnDebugResult(true, $importDebugMessages);
                    }
                    if (array_key_exists('skip_processor', $importProcessorResult) && $importProcessorResult['skip_processor'] === true) {
                        continue;
                    }
                }
                // Process sub processors
                foreach ($importProcessor->getSubProcessorClasses() as $subProcessorClass) {
                    $subProcessor = $this->objectManager->get($subProcessorClass);
                    try {
                        $this->dbConnection->beginTransaction();
                        $subProcessorResult = $subProcessor->setProfile($this->getProfile())->process($order, $updateData);
                        $this->dbConnection->commit();
                    } catch (\Exception $e) {
                        $this->dbConnection->rollBack();
                        $reflection = new \ReflectionClass($this->dbConnection);
                        if ($reflection->hasProperty('_isRolledBack')) {
                            $reflectionProperty = $reflection->getProperty('_isRolledBack');
                            $reflectionProperty->setAccessible(true);
                            $reflectionProperty->setValue($this->dbConnection, false);
                        }
                        if ($reflection->hasProperty('_transactionLevel')) {
                            $reflectionProperty = $reflection->getProperty('_transactionLevel');
                            $reflectionProperty->setAccessible(true);
                            $reflectionProperty->setValue($this->dbConnection, 0);
                        }
                        $importDebugMessages[] = __("Row %1 (ID: %2): Exception (Processor: %3): %4", $rowIdentifier, $objectId, $subProcessorClass, $e->getMessage());
                        $historyEntry->setEntityId($order->getId());
                        $historyEntry->setIncrementId($order->getIncrementId());
                        $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                        $historyEntry->setImportedAt(time());
                        $historyEntry->setImportData(json_encode($updateData));
                        $historyEntry->setStatus(History::RESULT_FAILED);
                        $historyEntry->setLog(implode("\n", $importDebugMessages));
                        if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                        throw $e;
                    }
                    if ($subProcessorResult !== true) {
                        $stopProcessing = false;
                        if (array_key_exists('stop', $subProcessorResult) && $subProcessorResult['stop'] === true) {
                            $stopProcessing = true;
                            $subProcessorResult['message'] .= __(" [Breaking error, stops import]");
                            $importDebugMessages[] = __("Row %1 (ID: %2): Processing error: %3", $rowIdentifier, $objectId, $subProcessorResult['message']);
                        } else {
                            $importDebugMessages[] = __("Row %1 (ID: %2): Processing notice: %3", $rowIdentifier, $objectId, $subProcessorResult['message']);
                        }
                        if ($stopProcessing) {
                            // History log
                            $historyEntry->setEntityId($order->getId());
                            $historyEntry->setIncrementId($order->getIncrementId());
                            $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                            $historyEntry->setImportedAt(time());
                            $historyEntry->setImportData(json_encode($updateData));
                            $historyEntry->setStatus(History::RESULT_FAILED);
                            $historyEntry->setLog(implode("\n", $importDebugMessages));
                            if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                            // End History Log
                            return $this->returnDebugResult(true, $importDebugMessages);
                        }
                    }
                }
            }

            if ($updateData['order']['__isObjectNew'] === true) {
                /** @var Quote $quote */
                $quote = $this->quoteFactory->create();
                $quote->setStoreId($order->getStoreId());
                if (!$order->getIncrementId()) {
                    // New order, add quote to get order# calculated
                    $quote->reserveOrderId();
                    $order->setIncrementId($quote->getReservedOrderId());
                }
                $quote->save();
                $order->setQuoteId($quote->getId());

                // Call inventory observer to reduce stock qtys for non-MSI installations
                try {
                    $inventoryObserver = $this->objectManager->get('\Magento\CatalogInventory\Observer\SubtractQuoteInventoryObserver');
                    $quoteItemCollection = $this->objectManager->create('\Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory')->create();
                    $quoteItemCollection->setQuote($quote);
                    $quoteItemFactory = $this->objectManager->create('\Magento\Quote\Model\Quote\ItemFactory');
                    foreach ($order->getAllItems() as $orderItem) {
                        if ($orderItem->getProductId() == 99999999) {
                            continue;
                        }
                        $quoteItem = $quoteItemFactory->create();
                        $quoteItem->setQuote($quote);
                        $quoteItem->addData(array_merge($orderItem->getData(), ['qty' => $orderItem->getQtyOrdered()]));
                        //$quoteItem->save();
                        $quoteItemCollection->addItem($quoteItem);
                    }
                    $quote->setItemsCollection($quoteItemCollection);
                    $observer = new \Magento\Framework\Event\Observer(
                        [
                            'event' => new \Magento\Framework\DataObject(
                                [
                                    'quote' => $quote,
                                    'order' => $order
                                ]
                            )
                        ]
                    );
                    $inventoryObserver->execute($observer);
                } catch (\Exception $e) {
                    $importDebugMessages[] = __("Row %1 (ID: %2): Warning during execution of SubtractQuoteInventoryObserver: %3", $rowIdentifier, $objectId, $e->getMessage());
                }

                // Estimate shipping
                if ($order->getShippingAmount() === null && $order->getBaseShippingAmount() === null && $order->getShippingInclTax() === null && $order->getBaseShippingInclTax() === null) {
                    $shippingMethods = [];
                    $shippingAddress = $quote->getShippingAddress();
                    $shippingAddress->addData($order->getShippingAddress()->toArray());
                    $shippingAddress->setCollectShippingRates(true);
                    $shippingAddress->collectShippingRates();

                    /*$this->totalsCollector->collectAddressTotals($quote, $shippingAddress);*/
                    $shippingRates = $shippingAddress->getGroupedAllShippingRates();
                    foreach ($shippingRates as $carrierRates) {
                        foreach ($carrierRates as $rate) {
                            $shippingMethods[] = $this->shippingMethodConverter->modelToDataObject($rate, $order->getBaseCurrencyCode());
                        }
                    }
                    usort($shippingMethods, function ($a, $b) { return ($a->getAmount() > $b->getAmount()); });
                    if (!empty($shippingMethods)) {
                        $cheapestMethod = $shippingMethods[0];
                        $shippingAddress->setShippingMethod(sprintf('%s_%s', $cheapestMethod->getCarrierCode(), $cheapestMethod->getMethodCode()));
                        $quote->collectTotals();
                        $order->setBaseShippingAmount($shippingAddress->getBaseShippingAmount());
                        $order->setShippingAmount($shippingAddress->getShippingAmount());
                        $order->setBaseShippingInclTax($shippingAddress->getBaseShippingInclTax());
                        $order->setShippingInclTax($shippingAddress->getShippingInclTax());
                        if (!$order->getShippingDescription()) {
                            $order->setShippingDescription(sprintf('%s - %s', $cheapestMethod->getCarrierTitle(), $cheapestMethod->getMethodTitle()));
                        }
                        if (!$order->getShippingMethod()) {
                            $order->setShippingMethod(sprintf('%s_%s', $cheapestMethod->getCarrierCode(), $cheapestMethod->getMethodCode()));
                        }
                        $importDebugMessages[] = __("Row %1 (ID: %2): Automatically calculated cheapest shipping cost: %3 (%4)", $rowIdentifier, $objectId, number_format($order->getBaseShippingInclTax(), 2) . ' ' . $order->getBaseCurrencyCode(), $order->getShippingDescription());
                    } else {
                        $importDebugMessages[] = __("Row %1 (ID: %2): Could not fetch shipping rates for quote. No shipping methods available.", $rowIdentifier, $objectId);
                    }
                }
            }

            // Save order
            try {
                $this->dbConnection->beginTransaction();
                if ($updateData['order']['__isObjectNew'] === true) {
                    $order = $this->collectTotals($order);
                }
                if (isset($updateData['state'])) {
                    $order->setData('state', $updateData['state']);
                }
                if (isset($updateData['status'])) {
                    $order->setData('status', $updateData['status']);
                }
                if ($order->getCustomerNote() !== '') {
                    $order->addStatusToHistory($order->getStatus() ? $order->getStatus() : 'processing', $order->getCustomerNote(), $order->getCustomerNoteNotify());
                }
                $addCommentAfterImport = $this->getConfig('add_comment_after_import');
                if ($addCommentAfterImport !== 'DISABLED') {
                    if (empty($addCommentAfterImport)) {
                        $historyMessage = $updateData['order']['__isObjectNew'] ? __('New order created.') : __('Order updated.');
                    } else {
                        $historyMessage = $updateData['order']['__isObjectNew'] ? __('New order created: %1', $addCommentAfterImport) : __('Order updated: %1', $addCommentAfterImport);
                    }
                    $order->addStatusToHistory(
                        $order->getStatus() ? $order->getStatus() : 'processing', __('XTENTO Order Import: %1', $historyMessage)
                    );
                }
                $this->eventManager->dispatch(
                    'xtento_orderimport_import_order_save_before',
                    [
                        'order' => $order,
                        'update_data' => $updateData
                    ]
                );

                try {
                    if ($updateData['order']['__isObjectNew'] === true) {
                        $order = $this->orderManagement->place($order);

                        $this->saveTaxRates($order);

                        $paymentTransactions = $order->getData('xtento_transactions');
                        if (!empty($paymentTransactions) && is_array($paymentTransactions)) {
                            foreach ($paymentTransactions as $paymentTransaction) {
                                $paymentTransaction->setOrder($order)->save();
                            }
                        }
                        $order->unsetData('xtento_transactions');

                        $customer = $this->registry->registry('xtento_orderimport_current_customer');
                        if ($order->getCustomerId() && $customer && $customer->getId()) {
                            $customerBillingAddressId = $customer->getData('xtento_customer_billing_address_id');
                            $customerShippingAddressId = $customer->getData('xtento_customer_shipping_address_id');
                            $order->getBillingAddress()->setCustomerId($order->getCustomerId())->setCustomerAddressId($customerBillingAddressId)->save();
                            $order->getShippingAddress()->setCustomerId($order->getCustomerId())->setCustomerAddressId($customerShippingAddressId)->save();
                        }
                    } else {
                        $order->save();
                    }
                } catch (\Exception $e) {
                    $this->dbConnection->rollBack();
                    $reflection = new \ReflectionClass($this->dbConnection);
                    if ($reflection->hasProperty('_isRolledBack')) {
                        $reflectionProperty = $reflection->getProperty('_isRolledBack');
                        $reflectionProperty->setAccessible(true);
                        $reflectionProperty->setValue($this->dbConnection, false);
                    }
                    if ($reflection->hasProperty('_transactionLevel')) {
                        $reflectionProperty = $reflection->getProperty('_transactionLevel');
                        $reflectionProperty->setAccessible(true);
                        $reflectionProperty->setValue($this->dbConnection, 0);
                    }
                    $importDebugMessages[] = __("Row %1 (ID: %2): Exception catched while saving order, could not create/update order. Exception: %3", $rowIdentifier, $objectId, $e->getMessage());
                    $historyEntry->setIncrementId(isset($updateData['order']['increment_id']) ? $updateData['order']['increment_id'] : null);
                    $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                    $historyEntry->setImportedAt(time());
                    $historyEntry->setImportData(json_encode($updateData));
                    $historyEntry->setStatus(History::RESULT_FAILED);
                    array_shift($importDebugMessages); // Remove first row
                    $historyEntry->setLog(implode("\n", $importDebugMessages));
                    if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                    return $this->returnDebugResult(false, $importDebugMessages);
                }

                // Re-store parent/child relations
                $itemCollection = $order->getItems();
                $itemsByEntityId = [];
                foreach ($itemCollection as $item) {
                    if ($item->getOldItemId() > 0) {
                        $itemsByEntityId[$item->getOldItemId()] = $item;
                    }
                }
                foreach ($itemCollection as $item) {
                    $oldParentItemId = $item->getOldParentItemId();
                    if ($oldParentItemId > 0 && isset($itemsByEntityId[$oldParentItemId])) {
                        $parentItem = $itemsByEntityId[$oldParentItemId];
                        $item->setParentItem($parentItem)
                            ->setParentItemId($parentItem->getId());
                        $item->save();
                        $parentItem->setSku($item->getSku());
                        $parentItem->setProductOptions(
                            array_merge(
                                $parentItem->getProductOptions(), [
                                'simple_name' => $item->getProduct()->getName(),
                                'simple_sku' => $item->getProduct()->getSku()
                            ]
                            )
                        );
                        //$parentItem->setProductOptions($parentItem->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct()));
                        $parentItem->save();
                    }
                }

                // Update order status
                $saveRequired = false;
                if (isset($updateData['order']['state']) && $order->getState() != $updateData['order']['state']) {
                    $order->setData('state', $updateData['order']['state']);
                    $saveRequired = true;
                }
                if (isset($updateData['order']['status']) && $order->getStatus() != $updateData['order']['status']) {
                    // Check is valid status
                    $isValidStatus = false;
                    foreach ($this->orderConfig->getStatuses() as $statusCode => $statusLabel) {
                        if (!empty($statusCode) && $statusCode == $updateData['order']['status']) {
                            $isValidStatus = true;
                            break 1;
                        }
                    }
                    if ($isValidStatus) {
                        $order->setStatus($updateData['order']['status']);
                        $saveRequired = true;
                    } else {
                        $importDebugMessages[] = __("Row %1 (ID: %2): Invalid order status specified, the following status is no existing status code: %3", $rowIdentifier, $objectId, $updateData['order']['status']);
                    }
                }
                if ($saveRequired) {
                    $order->save();
                }
                $this->dbConnection->commit();
                $importChanged = true;
            } catch (NoSuchEntityException $e) {
                // MSI throws an exception if fallback product (ID 9999999) does not exist in Magento
            } catch (\Exception $e) {
                $this->dbConnection->rollBack();
                $reflection = new \ReflectionClass($this->dbConnection);
                if ($reflection->hasProperty('_isRolledBack')) {
                    $reflectionProperty = $reflection->getProperty('_isRolledBack');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($this->dbConnection, false);
                }
                if ($reflection->hasProperty('_transactionLevel')) {
                    $reflectionProperty = $reflection->getProperty('_transactionLevel');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($this->dbConnection, 0);
                }
                $importDebugMessages[] = __("Row %1 (ID: %2): Exception catched while saving order, could not create/update order: %3", $rowIdentifier, $objectId, $e->getMessage());
                $historyEntry->setIncrementId(isset($updateData['order']['increment_id']) ? $updateData['order']['increment_id'] : null);
                $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
                $historyEntry->setImportedAt(time());
                $historyEntry->setImportData(json_encode($updateData));
                $historyEntry->setStatus(History::RESULT_FAILED);
                array_shift($importDebugMessages); // Remove first row
                $historyEntry->setLog(implode("\n", $importDebugMessages));
                if ($this->getImportType() != \Xtento\OrderImport\Model\Import::IMPORT_TYPE_MANUAL) $historyEntry->save(); // Rolled back transaction has not been completed correctly.
                return $this->returnDebugResult(false, $importDebugMessages);
            }
            if ($updateData['order']['__isObjectNew']) {
                $additionalInformation = __('New order created: %1', $order->getIncrementId());
                if ($this->getActionSettingByFieldBoolean('send_new_order_email', 'enabled')) {
                    $this->orderSender->send($order);
                }
            } else {
                $additionalInformation = __('Order updated: %1', $order->getIncrementId());
            }
            // History log
            $historyEntry->setEntityId($order->getId());
            $historyEntry->setIncrementId($order->getIncrementId());
            $historyEntry->setExtOrderId(isset($updateData['order']['ext_order_id']) ? $updateData['order']['ext_order_id'] : null);
            $historyEntry->setImportedAt(time());
            $historyEntry->setImportData(json_encode($updateData));
            $historyEntry->setStatus(History::RESULT_SUCCESSFUL);
            $historyEntry->setLog($additionalInformation);
            $historyEntry->save();
            // End History Log
            $importDebugMessages[] = __("Row %1 (ID: %2): Order has been processed: %3", $rowIdentifier, $objectId, $additionalInformation);
            $this->eventManager->dispatch(
                'xtento_orderimport_import_order_save_after',
                [
                    'order' => $order,
                    'is_new' => $updateData['order']['__isObjectNew']
                ]
            );
        }

        // Set store and locale, so email templates and locales are sent correctly
        if ($order->getStoreId()) {
            // Set store and locale, so email templates and locales are sent correctly
            $this->appEmulation->startEnvironmentEmulation($order->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        // Apply post-processing actions
        foreach ($this->mappingActionFactory->create()->getImportActions() as $entity => $actions) {
            foreach ($actions as $actionId => $actionData) {
                if (isset($actionData['class']) && isset($actionData['method'])) {
                    $actionModel = $this->objectManager->create($actionData['class']);
                    if ($actionModel) {
                        try {
                            $actionModel->setData('update_data', $updateData);
                            $actionModel->setData('order', $order);
                            $actionModel->setData('actions', $this->getActions());
                            $actionModel->{$actionData['method']}();
                            $importDebugMessages = array_merge($importDebugMessages, $actionModel->getDebugMessages());
                            if ($actionModel->getHasUpdatedObject()) {
                                $importChanged = true;
                            }
                        } catch (\Exception $e) {
                            // Don't break execution, but log the order related error.
                            $errorMessage = __(
                                "Exception catched for order '%1' while executing action '%2::%3':\n%4",
                                $order->getIncrementId(),
                                $actionData['class'],
                                $actionData['method'],
                                $e->getMessage()
                            );
                            $importDebugMessages[] = $errorMessage;
                            $this->registry->registry('orderimport_log')->setResult(Log::RESULT_WARNING);
                            $this->registry->registry('orderimport_log')->addResultMessage($errorMessage);
                            // Re-load order to "kill" changes made in order object by invoice/shipment creation
                            $order = $this->loadOrder($updateData['order']);
                            #return $this->returnDebugResult($importChanged, $importDebugMessages);
                            continue;
                        }
                    }
                }
            }
        }

        // Reset locale.
        $this->appEmulation->stopEnvironmentEmulation();

        return $this->returnDebugResult($importChanged, $importDebugMessages);
    }


    /**
     * @param $order
     *
     * @return mixed
     */
    protected function collectTotals($order)
    {
        $baseSubtotal = $order->getBaseSubtotal();
        $subtotal = $order->getSubtotal();
        $baseSubtotalInclTax = $order->getBaseSubtotalInclTax();
        $subtotalInclTax = $order->getSubtotalInclTax();
        $baseGrandTotal = $order->getBaseGrandTotal();
        $grandTotal = $order->getGrandTotal();
        $baseTaxAmount = $order->getBaseTaxAmount();
        $taxAmount = $order->getTaxAmount();
        $baseShippingAmount = $order->getBaseShippingAmount();
        $shippingAmount = $order->getShippingAmount();
        $baseShippingInclTax = $order->getBaseShippingInclTax();
        $shippingInclTax = $order->getShippingInclTax();
        $baseDiscountAmount = $order->getBaseDiscountAmount();
        $discountAmount = $order->getDiscountAmount();

        $this->calculatedTaxRatesByType = [];

        // Shipping tax
        if ($baseShippingAmount === null && $shippingAmount !== null) {
            $baseShippingAmount = $shippingAmount;
        }
        if ($shippingAmount === null && $baseShippingAmount !== null) {
            $shippingAmount = $baseShippingAmount;
        }
        if ($baseShippingInclTax === null && $shippingInclTax !== null) {
            $baseShippingInclTax = $shippingInclTax;
            $order->setBaseShippingInclTax($baseShippingInclTax);
        }
        if ($shippingInclTax === null && $baseShippingInclTax !== null) {
            $shippingInclTax = $baseShippingInclTax;
            $order->setShippingInclTax($shippingInclTax);
        }
        if ($baseShippingInclTax === null && $baseShippingAmount !== null) {
            $baseShippingInclTax = $baseShippingAmount;
        }
        if ($shippingInclTax === null && $shippingAmount !== null) {
            $shippingInclTax = $shippingAmount;
        }
        if ($baseShippingAmount == 0 && $baseShippingInclTax !== null) {
            $baseShippingAmount = $baseShippingInclTax;
            $order->setBaseShippingAmount($baseShippingInclTax);
        }
        if ($shippingAmount == 0 && $shippingInclTax !== null) {
            $shippingAmount = $shippingInclTax;
            $order->setShippingAmount($shippingInclTax);
        }
        $baseShippingTaxAmount = $order->getBaseShippingTaxAmount();
        if (!$baseShippingTaxAmount && $baseShippingInclTax > 0 && $baseShippingAmount > 0) {
            $baseShippingTaxAmount = $baseShippingInclTax - $baseShippingAmount;
        }
        $shippingTaxAmount = $order->getShippingTaxAmount();
        if (!$shippingTaxAmount && $shippingInclTax > 0 && $shippingAmount > 0) {
            $shippingTaxAmount = $shippingInclTax - $shippingAmount;
        }
        if ($shippingInclTax === null && $shippingTaxAmount && $shippingAmount) {
            $shippingInclTax = $shippingAmount + $shippingTaxAmount;
            $order->setShippingInclTax($shippingInclTax);
        }
        if ($baseShippingInclTax === null && $baseShippingTaxAmount && $baseShippingAmount) {
            $baseShippingInclTax = $baseShippingAmount + $baseShippingTaxAmount;
            $order->setBaseShippingInclTax($baseShippingInclTax);
        }
        if ($baseShippingTaxAmount <= 0 && $shippingTaxAmount <= 0) {
            $store = $order->getStore();
            $shippingTaxClass = $this->taxConfig->getShippingTaxClass($store);
            $request = $this->taxCalculation->getRateRequest($order->getShippingAddress(), $order->getBillingAddress(), $order->getCustomerTaxClassId(), $store);
            if ($rate = $this->taxCalculation->getRate($request->setProductClassId($shippingTaxClass))) {
                $shippingTaxAmount = round($shippingAmount * $rate / 100, 4);
                $baseShippingTaxAmount = round($baseShippingAmount * $rate / 100, 4);
                if (!$baseShippingInclTax) $baseShippingInclTax = $baseShippingAmount + $baseShippingTaxAmount;
                if (!$shippingInclTax) $shippingInclTax = $shippingAmount + $shippingTaxAmount;
                $order->setBaseShippingInclTax($baseShippingInclTax);
                $order->setShippingInclTax($shippingInclTax);
                // Add to tax rates
                $taxPercent = str_replace('.', '_', sprintf('%.4f', $rate));
                $this->calculatedTaxRatesByType['shipping'][$taxPercent] = ['percent' => $rate, 'base_amount' => $baseShippingTaxAmount, 'amount' => $shippingTaxAmount];
            }
        } else {
            if ($baseShippingTaxAmount === null && $shippingTaxAmount !== null) {
                $baseShippingTaxAmount = $shippingTaxAmount;
            }
            if ($shippingTaxAmount === null && $baseShippingTaxAmount !== null) {
                $shippingTaxAmount = $baseShippingTaxAmount;
            }
        }

        if ($baseSubtotal === null && $subtotal !== null) {
            $baseSubtotal = $subtotal;
        }
        if ($baseSubtotal === null) {
            $totalQtyOrdered = 0;
            $totalWeight = 0;
            foreach ($order->getAllItems() as $orderItem) {
                $baseSubtotal += $orderItem->getBaseRowTotal();
                $subtotal += $orderItem->getRowTotal();
                $baseSubtotalInclTax += $orderItem->getBaseRowTotalInclTax();
                $subtotalInclTax += $orderItem->getRowTotalInclTax();
                $totalQtyOrdered += $orderItem->getQtyOrdered();
                $totalWeight += $orderItem->getWeight();
            }

            if (!$order->getTotalQtyOrdered()) $order->setTotalQtyOrdered($totalQtyOrdered);
            if (!$order->getWeight()) $order->setWeight($totalWeight);
            $order->setBaseSubtotal($baseSubtotal);
            $order->setSubtotal($subtotal);
            $order->setBaseSubtotalInclTax($baseSubtotalInclTax);
            $order->setSubtotalInclTax($subtotalInclTax);
        }
        if ($baseGrandTotal === null && $grandTotal !== null) {
            $baseGrandTotal = $grandTotal;
        }
        if ($baseGrandTotal === null) {
            foreach ($order->getAllItems() as $orderItem) {
                $baseTaxAmount += $orderItem->getBaseTaxAmount();
                $taxAmount += $orderItem->getTaxAmount();
                $baseGrandTotal += $orderItem->getBaseRowTotalInclTax();
                $grandTotal += $orderItem->getRowTotalInclTax();

                // Add to tax rates
                $taxPercent = str_replace('.', '_', sprintf('%.4f', $orderItem->getTaxPercent()));
                if (isset($this->calculatedTaxRatesByType['product'][$taxPercent])) {
                    $this->calculatedTaxRatesByType['product'][$taxPercent]['base_amount'] += $baseTaxAmount;
                    $this->calculatedTaxRatesByType['product'][$taxPercent]['amount'] += $taxAmount;
                } else {
                    $this->calculatedTaxRatesByType['product'][$taxPercent] = ['percent' => $orderItem->getTaxPercent(), 'base_amount' => $baseShippingTaxAmount, 'amount' => $shippingTaxAmount];
                }
            }

            $baseGrandTotal += $baseShippingInclTax;
            $grandTotal += $shippingInclTax;
            $baseGrandTotal -= $baseDiscountAmount;
            $grandTotal -= $discountAmount;

            $order->setBaseGrandTotal($baseGrandTotal);
            $order->setGrandTotal($grandTotal);
        }
        if ($order->getBaseTaxAmount() === null && $order->getTaxAmount() !== null) {
            $order->setBaseTaxAmount($order->getTaxAmount());
        }
        if ($order->getBaseTaxAmount() === null) {
            $baseTaxAmount += $baseShippingTaxAmount;
            $taxAmount += $shippingTaxAmount;
            $order->setBaseTaxAmount($baseTaxAmount);
            $order->setTaxAmount($taxAmount);
        }

        return $order;
    }

    /**
     * @param $order
     */
    protected function saveTaxRates($order)
    {
        foreach ($this->calculatedTaxRatesByType as $taxType => $calculatedTaxRates) {
            // Save tax rates
            foreach ($calculatedTaxRates as &$calculatedTaxRate) {
                $taxPercent = $calculatedTaxRate['percent'];
                if (floor($taxPercent) != $taxPercent) {
                    $taxPercent = number_format($taxPercent, 2);
                } else {
                    $taxPercent = number_format($taxPercent, 0);
                }
                $taxTitle = $taxPercent . '%';
                $data = [
                    'order_id' => $order->getEntityId(),
                    'code' => $taxTitle,
                    'title' => $taxTitle,
                    'hidden' => 0,
                    'percent' => $calculatedTaxRate['percent'],
                    'priority' => 0,
                    'position' => 0,
                    'amount' => $calculatedTaxRate['amount'],
                    'base_amount' => $calculatedTaxRate['base_amount'],
                    'process' => 0,
                    'base_real_amount' => $calculatedTaxRate['base_amount']
                ];

                /** @var $orderTax \Magento\Tax\Model\Sales\Order\Tax */
                $orderTax = $this->orderTaxFactory->create();
                $orderTax->setData($data)->save();
                $calculatedTaxRate['tax_id'] = $orderTax->getTaxId();
            }
            // Save item tax rates
            $addedOrderItems = [];
            foreach ($calculatedTaxRates as $calculatedTaxRate) {
                if ($taxType === 'product') {
                    foreach ($order->getAllItems() as $orderItem) {
                        if ($calculatedTaxRate['percent'] !== $orderItem->getTaxPercent()) {
                            continue;
                        }
                        if (in_array($orderItem->getId(), $addedOrderItems)) {
                            continue;
                        }
                        $addedOrderItems[] = $orderItem->getId();
                        $data = [
                            'item_id' => $orderItem->getId(),
                            'tax_id' => $calculatedTaxRate['tax_id'],
                            'tax_percent' => $orderItem->getTaxPercent(),
                            'associated_item_id' => null,
                            'amount' => $orderItem->getTaxAmount(),
                            'base_amount' => $orderItem->getBaseTaxAmount(),
                            'real_amount' => $orderItem->getTaxAmount(),
                            'real_base_amount' => $orderItem->getBaseTaxAmount(),
                            'taxable_item_type' => $taxType,
                        ];
                        /** @var $taxItem \Magento\Sales\Model\Order\Tax\Item */
                        $taxItem = $this->orderTaxItemFactory->create();
                        $taxItem->setData($data)->save();
                    }
                }
                if ($taxType === 'shipping') {
                    $data = [
                        'item_id' => null,
                        'tax_id' => $calculatedTaxRate['tax_id'],
                        'tax_percent' => $calculatedTaxRate['percent'],
                        'associated_item_id' => null,
                        'amount' => $calculatedTaxRate['amount'],
                        'base_amount' => $calculatedTaxRate['base_amount'],
                        'real_amount' => $calculatedTaxRate['amount'],
                        'real_base_amount' => $calculatedTaxRate['base_amount'],
                        'taxable_item_type' => $taxType,
                    ];
                    /** @var $taxItem \Magento\Sales\Model\Order\Tax\Item */
                    $taxItem = $this->orderTaxItemFactory->create();
                    $taxItem->setData($data)->save();
                }
            }
        }
    }

    protected function shouldImportOrders()
    {
        $actionName = 'import_order';
        $fieldToRetrieve = 'default_value';
        $actions = $this->getActions();
        foreach ($actions as $actionId => $actionData) {
            if ($actionData['field'] == $actionName) {
                if (isset($actionData[$fieldToRetrieve])) {
                    return $actionData[$fieldToRetrieve];
                } else {
                    return false;
                }
            }
        }
        return false;
    }

    protected function returnDebugResult($changed, $debugMessages)
    {
        $this->appEmulation->stopEnvironmentEmulation();
        //$newCustomer = Mage::getModel('customer/customer');
        //Mage::getModel('customer/session')->setCustomer($newCustomer);
        $this->registry->unregister('xtento_orderimport_current_customer');
        $this->registry->unregister('rule_data');
        $importResult = ['changed' => $changed, 'debug' => implode("\n", $debugMessages)];
        return $importResult;
    }

    /**
     * After the import ran
     */
    public function afterRun()
    {
        // End of routine
        #$this->getLogEntry()->addDebugMessage('Done: afterRun()');
        return $this;
    }
}