<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-20T08:14:15+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Entity/Customer.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Entity;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Xtento\OrderImport\Helper\Entity;
use Xtento\OrderImport\Model\History;
use Xtento\OrderImport\Model\HistoryFactory;
use Xtento\OrderImport\Model\Import;
use Xtento\OrderImport\Model\Log;
use Xtento\OrderImport\Model\Processor\Mapping\ActionFactory;

class Customer extends AbstractEntity
{
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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Customer constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param Registry $frameworkRegistry
     * @param ManagerInterface $eventManagerInterface
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param ActionFactory $mappingActionFactory
     * @param ObjectManagerInterface $objectManager
     * @param Entity $entityHelper
     * @param HistoryFactory $historyFactory
     * @param Import\Processors $importProcessors
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Registry $frameworkRegistry,
        ManagerInterface $eventManagerInterface,
        \Magento\Store\Model\App\Emulation $appEmulation,
        ActionFactory $mappingActionFactory,
        ObjectManagerInterface $objectManager,
        Entity $entityHelper,
        HistoryFactory $historyFactory,
        Import\Processors $importProcessors,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->eventManager = $eventManagerInterface;
        $this->appEmulation = $appEmulation;
        $this->mappingActionFactory = $mappingActionFactory;
        $this->objectManager = $objectManager;
        $this->entityHelper = $entityHelper;
        $this->historyFactory = $historyFactory;
        $this->importProcessors = $importProcessors;
        $this->customerFactory = $customerFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
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

    /**
     * @param $customerData
     *
     * @return \Magento\Customer\Model\Customer
     */
    protected function loadCustomer($customerData)
    {
        // Identify customer and return $customer
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create();
        if (isset($customerData['id'])) {
            $customer = $this->customerFactory->create()->load($customerData['id']);
        }
        if (!$customer->getId() && isset($customerData['email'])) {
            if (!isset($customerData['website_id'])) {
                if (!isset($customerData['store_id'])) {
                    $website = $this->storeManager->getWebsite(true);
                    $customerData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
                }
                $customerData['website_id'] = $this->storeManager->getStore($customerData['store_id'])->getWebsiteId();
            }
            $customer = $this->customerFactory->create()->setWebsiteId($customerData['website_id'])->loadByEmail($customerData['email']);
        }
        if (!$customer || !$customer->getId()) {
            if (!isset($customerData['email']) || empty($customerData['email'])) {
                $host = $this->scopeConfig->getValue(\Magento\Sales\Model\AdminOrder\Create::XML_PATH_DEFAULT_EMAIL_DOMAIN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $customerData['store_id']);
                $account = 'import_' . time() . rand(1, 10000);
                $email = $account . '@' . $host;
                $customer->setEmail($email);
            }
        }
        $this->registry->register('xtento_orderimport_current_customer', $customer, true);

        return $customer;
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
        if (!isset($updateData['customer'])) {
            $importDebugMessages[] = __("No customer data found, not starting customer processors.");
            $importChanged = false;
            return $this->returnDebugResult($importChanged, $importDebugMessages);
        }

        if (!$this->getTestMode()) {
            $historyEntry = $this->historyFactory->create();
            $historyEntry->setProfileId($this->getProfile()->getId());
            $historyEntry->setLogId($this->registry->registry('orderimport_log')->getId());
            $historyEntry->setEntity(Import::ENTITY_CUSTOMER);
        } else {
            $historyEntry = false;
        }

        $objectId = 'not set';
        if (isset($updateData['customer']['id'])) {
            $objectId = $updateData['customer']['id'];
        }

        // Result (and debug information) returned
        $importChanged = false;
        $importDebugMessages = [];
        $importMode = $this->getConfig('import_mode');

        // Prepare updateData
        $updateData['__importMode'] = $importMode;
        $updateData['__rowIdentifier'] = $rowIdentifier;

        // Check if object already exists
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->loadCustomer($updateData['customer']);
        if (isset($updateData['customer'])) {
            $updateData['customer']['__isObjectNew'] = true;
            if ($customer && $customer->getId()) {
                $updateData['customer']['__isObjectNew'] = false;
                $objectId = sprintf('%s', $customer->getId());
            }
        }

        // Import mode validations
        if ($importMode == Import::IMPORT_MODE_NEW && $updateData['customer']['__isObjectNew'] === false) {
            $importDebugMessages[] = __("Row %1 (ID: %2): Import mode is 'New only', but customer exists already. Skipping.", $rowIdentifier, $objectId);
            return $this->returnDebugResult(false, $importDebugMessages);
        }
        if ($importMode == Import::IMPORT_MODE_DELETE && $updateData['customer']['__isObjectNew'] === true) {
            $importDebugMessages[] = __("Row %1 (ID: %2): Import mode is 'Delete', but customer does not exist in Magento. Skipping.", $rowIdentifier, $objectId);
            return $this->returnDebugResult($importChanged, $importDebugMessages);
        }
        if ($importMode == Import::IMPORT_MODE_UPDATE && $updateData['customer']['__isObjectNew'] === true) {
            $importDebugMessages[] = __("Row %1 (ID: %2): Import mode is 'Update, Skip new', but customer does not exist in Magento. Skipping.", $rowIdentifier, $objectId);
            return $this->returnDebugResult($importChanged, $importDebugMessages);
        }

        // Register update data for third party processing
        $this->registry->unregister('xtento_orderimport_updatedata');
        $this->registry->register('xtento_orderimport_updatedata', $updateData);

        if ($this->shouldImportCustomers()) {
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
                    $historyEntry->setEntityId($customer->getId());
                    $historyEntry->setIncrementId($customer->getEmail());
                    $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
                    $historyEntry->setImportedAt(time());
                    $historyEntry->setImportData(json_encode($updateData));
                    $historyEntry->setLog(__('Customer FAILED validation, stopping: %1', implode("\n", $importDebugMessages)));
                    $historyEntry->setStatus(History::RESULT_FAILED);
                    $historyEntry->save();
                    // End History Log
                    $importDebugMessages[] = __("Row %1 (ID: %2): FAILED validation. Potential errors can be seen above. Stopping.", $rowIdentifier, $objectId);
                    return $this->returnDebugResult(true, $importDebugMessages);
                }
                $importDebugMessages[] = __("Row %1 (ID: %2): Ready to process.", $rowIdentifier, $objectId);
            }
        } else {
            $importDebugMessages[] = __("Row %1 (ID: %2): \"Import Customers\" action in \"Actions\" tab not enabled, not running customer import procedure.", $rowIdentifier, $objectId);
        }

        $this->eventManager->dispatch(
            'xtento_orderimport_process_customer_before',
            [
                'import_profile' => $this->getProfile(),
                'update_data' => &$updateData,
                'customer' => $customer
            ]
        );

        // Emulate correct store
        if ($customer->getStoreId()) {
            $this->appEmulation->startEnvironmentEmulation($customer->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        // Run import
        #var_dump($validationResults);

        if ($importMode == Import::IMPORT_MODE_DELETE) {
            $customer->delete();
            // History log
            $historyEntry->setEntityId($customer->getId());
            $historyEntry->setIncrementId($customer->getEmail());
            $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
            $historyEntry->setImportedAt(time());
            $historyEntry->setLog(__('Customer has been deleted.'));
            $historyEntry->setImportData(json_encode($updateData));
            $historyEntry->setStatus(History::RESULT_SUCCESSFUL);
            $historyEntry->save();
            // End History Log
            $importDebugMessages[] = __("Row %1 (ID: %2): Customer has been deleted.", $rowIdentifier, $objectId);
            return $this->returnDebugResult(true, $importDebugMessages);
        } else if ($this->shouldImportCustomers()) {
            // Process / update customer
            foreach ($importProcessorClasses as $importProcessorClass) {
                $importProcessor = $this->objectManager->get($importProcessorClass);
                try {
                    $importProcessorResult = $importProcessor->setProfile($this->getProfie())->process($customer, $updateData);
                } catch (\Exception $e) {
                    $importDebugMessages[] = __("Row %1 (ID: %2): Exception (Processor: %3): %4", $rowIdentifier, $objectId, $importProcessorClass, $e->getMessage());
                    $historyEntry->setEntityId($customer->getId());
                    $historyEntry->setIncrementId($customer->getEmail());
                    $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
                    $historyEntry->setImportedAt(time());
                    $historyEntry->setImportData(json_encode($updateData));
                    $historyEntry->setLog(implode("\n", $importDebugMessages));
                    $historyEntry->setStatus(History::RESULT_FAILED);
                    $historyEntry->save();
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
                        $historyEntry->setEntityId($customer->getId());
                        $historyEntry->setIncrementId($customer->getEmail());
                        $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
                        $historyEntry->setImportedAt(time());
                        $historyEntry->setImportData(json_encode($updateData));
                        $historyEntry->setLog(implode("\n", $importDebugMessages));
                        $historyEntry->setStatus(History::RESULT_FAILED);
                        $historyEntry->save();
                        // End History Log
                        return $this->returnDebugResult(true, $importDebugMessages);
                    }
                    if (array_key_exists('skip_processor', $importProcessorResult) && $importProcessorResult['skip_processor'] === true) {
                        continue;
                    }
                }
                // Save customer before processing sub processors, so customer is saved in DB
                try {
                    $this->eventManager->dispatch(
                        'xtento_orderimport_import_customer_save_before',
                        [
                            'customer' => $customer,
                            'update_data' => $updateData
                        ]
                    );
                    $customer->save();
                    $objectId = sprintf('%s (%s)', $customer->getId(), $customer->getEmail());
                    $importChanged = true;
                } catch (\Exception $e) {
                    $importDebugMessages[] = __("Row %1 (ID: %2): Exception catched while saving customer, could not create/update customer: %3", $rowIdentifier, $objectId, $e->getMessage());
                    $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
                    $historyEntry->setImportedAt(time());
                    $historyEntry->setImportData(json_encode($updateData));
                    $historyEntry->setStatus(History::RESULT_FAILED);
                    array_shift($importDebugMessages); // Remove first row
                    $historyEntry->setLog(implode("\n", $importDebugMessages));
                    $historyEntry->save();
                    return $this->returnDebugResult(false, $importDebugMessages);
                }
                // Process sub processors
                foreach ($importProcessor->getSubProcessorClasses() as $subProcessorClass) {
                    $subProcessor = $this->objectManager->get($subProcessorClass);
                    try {
                        $subProcessorResult = $subProcessor->setProfile($this->getProfile())->process($customer, $updateData);
                    } catch (\Exception $e) {
                        $importDebugMessages[] = __("Row %1 (ID: %2): Exception (Processor: %3): %4", $rowIdentifier, $objectId, $subProcessorClass, $e->getMessage());
                        $historyEntry->setEntityId($customer->getId());
                        $historyEntry->setIncrementId($customer->getEmail());
                        $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
                        $historyEntry->setImportedAt(time());
                        $historyEntry->setImportData(json_encode($updateData));
                        $historyEntry->setLog(implode("\n", $importDebugMessages));
                        $historyEntry->setStatus(History::RESULT_FAILED);
                        $historyEntry->save();
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
                            $historyEntry->setEntityId($customer->getId());
                            $historyEntry->setIncrementId($customer->getEmail());
                            $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
                            $historyEntry->setImportedAt(time());
                            $historyEntry->setImportData(json_encode($updateData));
                            $historyEntry->setLog(implode("\n", $importDebugMessages));
                            $historyEntry->setStatus(History::RESULT_FAILED);
                            $historyEntry->save();
                            // End History Log
                            return $this->returnDebugResult(true, $importDebugMessages);
                        }
                    }
                }
            }

            if ($updateData['customer']['__isObjectNew']) {
                $additionalInformation = __('New customer created: %1', $objectId);
            } else {
                $additionalInformation = __('Customer updated: %1', $objectId);
            }
            // History log
            $historyEntry->setEntityId($customer->getId());
            $historyEntry->setIncrementId($customer->getEmail());
            $historyEntry->setExtOrderId(isset($updateData['customer']['ext_customer_id']) ? $updateData['customer']['ext_customer_id'] : null);
            $historyEntry->setImportedAt(time());
            $historyEntry->setImportData(json_encode($updateData));
            $historyEntry->setLog($additionalInformation);
            $historyEntry->setStatus(History::RESULT_SUCCESSFUL);
            $historyEntry->save();
            // End History Log
            $importDebugMessages[] = __("Row %1 (ID: %2): Customer has been processed: %3", $rowIdentifier, $objectId, $additionalInformation);
            $this->eventManager->dispatch(
                'xtento_orderimport_import_customer_save_after',
                [
                    'customer' => $customer
                ]
            );
        }

        // Set store and locale, so email templates and locales are sent correctly
        if ($customer->getStoreId()) {
            // Set store and locale, so email templates and locales are sent correctly
            $this->appEmulation->startEnvironmentEmulation($customer->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        // Apply post-processing actions
        /*foreach ($this->mappingActionFactory->create()->getImportActions() as $entity => $actions) {
            foreach ($actions as $actionId => $actionData) {
                if (isset($actionData['class']) && isset($actionData['method'])) {
                    $actionModel = $this->objectManager->create($actionData['class']);
                    if ($actionModel) {
                        try {
                            $actionModel->setData('update_data', $updateData);
                            $actionModel->setData('customer', $customer);
                            $actionModel->setData('actions', $this->getActions());
                            $actionModel->{$actionData['method']}();
                            $importDebugMessages = array_merge($importDebugMessages, $actionModel->getDebugMessages());
                            if ($actionModel->getHasUpdatedObject()) {
                                $importChanged = true;
                            }
                        } catch (\Exception $e) {
                            // Don't break execution, but log the order related error.
                            $errorMessage = __(
                                "Exception catched for customer ID '%1' while executing action '%2::%3':\n%4",
                                $customer->getId(),
                                $actionData['class'],
                                $actionData['method'],
                                $e->getMessage()
                            );
                            $importDebugMessages[] = $errorMessage;
                            $this->registry->registry('orderimport_log')->setResult(Log::RESULT_WARNING);
                            $this->registry->registry('orderimport_log')->addResultMessage($errorMessage);
                            #return $this->returnDebugResult($importChanged, $importDebugMessages);
                            continue;
                        }
                    }
                }
            }
        }*/

        // Reset locale.
        $this->appEmulation->stopEnvironmentEmulation();

        return $this->returnDebugResult($importChanged, $importDebugMessages);
    }


    protected function shouldImportCustomers()
    {
        $actionName = 'import_customer';
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