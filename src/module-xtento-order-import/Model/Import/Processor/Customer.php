<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-21T15:50:04+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Customer.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Xtento\OrderImport\Model\Import;

class Customer extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $customerGroupFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Customer constructor.
     *
     * @param Registry $registry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        array $data = []
    ) {
        parent::__construct($data);
        $this->registry = $registry;
        $this->customerFactory = $customerFactory;
        $this->customerGroupFactory = $groupFactory;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @return array
     */
    public function getSubProcessorClasses()
    {
        return [
            '\Xtento\OrderImport\Model\Import\Processor\Customer\Address',
        ];
    }

    /**
     * @param $updateData
     *
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validate(&$updateData)
    {
        if (!isset($updateData['customer'])) {
            return [
                'skip_processor' => true,
                'message' => __(
                    'No customer data found for current row. Skipping "customer" processor.'
                )
            ];
        }
        $customerData = &$updateData['customer'];

        // Validate store
        if ($this->isObjectNew($updateData, 'customer')) {
            $allStores = $this->storeManager->getStores();
            if (count($allStores) > 1) { // Otherwise there's just one store, that can be used.
                $storeFound = false;
                // Validate store_code
                if (array_key_exists('store_code', $customerData) && !empty($customerData['store_code'])) {
                    foreach ($allStores as $store) {
                        if ($customerData['store_code'] == $store->getCode()) {
                            $customerData['store_id'] = $store->getId();
                            if (!array_key_exists('website_id', $customerData)) {
                                $customerData['website_id'] = $this->storeManager->getStore($store->getId())->getWebsiteId();
                            }
                            unset($customerData['store_code']);
                            $storeFound = true;
                            break;
                        }
                    }
                }
                // Validate store_id
                if (array_key_exists('store_id', $customerData) && !empty($customerData['store_id'])) {
                    foreach ($allStores as $store) {
                        if ($customerData['store_id'] == $store->getId()) {
                            if (!array_key_exists('website_id', $customerData)) {
                                $customerData['website_id'] = $this->storeManager->getStore($store->getId())->getWebsiteId();
                            }
                            $storeFound = true;
                            break;
                        }
                    }
                }
                if (!$storeFound) {
                    $website = $this->storeManager->getWebsite(true);
                    $customerData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
                    $customerData['website_id'] = $website->getId();
                    /*return array(
                        'stop' => true,
                        'message' => __(
                            'No store_id or store_code set for order. This is required to import an order. Or, set a default value for one of these fields so the order can be imported into a store. Go into the mapping, and add a mapping for "store_id" and set the default value to "1" for example. (Store IDs can be found at System > Manage Stores)'
                        )
                    );*/
                }
                if (!isset($customerData['website_id'])) {
                    $customerData['website_id'] = $this->storeManager->getStore($customerData['store_id'])->getWebsiteId();
                }
            } else {
                if (!isset($customerData['store_id'])) {
                    $website = $this->storeManager->getWebsite(true);
                    $customerData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
                }
                if (!isset($customerData['website_id'])) {
                    $customerData['website_id'] = $this->storeManager->getStore($customerData['store_id'])->getWebsiteId();
                }
            }
        } else {
            if (!isset($customerData['store_id'])) {
                $website = $this->storeManager->getWebsite(true);
                $customerData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
            }
            if (!isset($customerData['website_id'])) {
                $customerData['website_id'] = $this->storeManager->getStore($customerData['store_id'])->getWebsiteId();
            }
        }

        // Import mode validations
        // New only mode
        if ($this->getImportMode($updateData) == Import::IMPORT_MODE_NEW) {
            if (!$this->isObjectNew($updateData, 'customer')) {
                return [
                    'stop' => true,
                    'message' => __(
                        'Import mode is set to "Import new only", but this customer exists already.'
                    )
                ];
            }
        }
        // Delete mode
        if ($this->getImportMode($updateData) == Import::IMPORT_MODE_DELETE) {
            if ($this->isObjectNew($updateData, 'customer')) {
                return [
                    'stop' => true,
                    'message' => __(
                        'Import mode is set to "Delete objects", but this customer does not exist in Magento.'
                    )
                ];
            }
        }

        // Check if customer_id is supplied or if a new customer can be created
        // More validations
        return true;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $updateData
     *
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function process(&$customer, &$updateData)
    {
        $customerData = &$updateData['customer'];

        $this->eventManager->dispatch('xtento_orderimport_customer_processor_before', ['customer' => $customer, 'update_data' => $updateData]);

        $warnings = [];
        $warnings = array_merge($warnings, $this->importCustomerData($customer, $customerData));
        $warnings = array_merge($warnings, $this->importStoreData($customer, $customerData));

        $this->eventManager->dispatch('xtento_orderimport_customer_processor_after', ['customer' => $customer, 'update_data' => $updateData]);

        if (!empty($warnings)) {
            return [
                'stop' => false,
                'message' => implode(", ", $warnings)
            ];
        } else {
            return true;
        }
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $customerData
     *
     * @return array
     */
    protected function importCustomerData($customer, $customerData)
    {
        $warnings = [];
        foreach ($customerData as $field => $value) {
            if (is_array($value)) {
                continue;
            }
            $ignoredFields = ['id', 'store_id'];
            if (array_key_exists($field, $ignoredFields)) {
                continue;
            }
            $customer->setData($field, $value);
        }
        if (isset($customerData['password_unencrypted'])) {
            $customer->setPassword($customerData['password_unencrypted']);
        }
        return $warnings;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $customerData
     *
     * @return array
     */
    protected function importStoreData($customer, $customerData)
    {
        $warnings = [];

        if ($customerData['__isObjectNew']) {
            $customer->setStoreId($customerData['store_id']);
            $customer->setWebsiteId($customerData['website_id']);
        }

        return $warnings;
    }
}