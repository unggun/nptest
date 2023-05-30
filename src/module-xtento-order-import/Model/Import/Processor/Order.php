<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-10-31T21:19:15+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Order.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Xtento\OrderImport\Model\Import;

class Order extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    protected $giftMessageFactory;

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
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Xtento\XtCore\Helper\Utils
     */
    protected $utilsHelper;

    /**
     * Order constructor.
     *
     * @param Registry $registry
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param \Magento\GiftMessage\Model\MessageFactory $giftMessageFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Xtento\XtCore\Helper\Utils $utilsHelper
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\GiftMessage\Model\MessageFactory $giftMessageFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Xtento\XtCore\Helper\Utils $utilsHelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->registry = $registry;
        $this->orderFactory = $orderFactory;
        $this->orderConfig = $orderConfig;
        $this->shippingConfig = $shippingConfig;
        $this->giftMessageFactory = $giftMessageFactory;
        $this->customerFactory = $customerFactory;
        $this->customerGroupFactory = $groupFactory;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
        $this->localeDate = $localeDate;
        $this->utilsHelper = $utilsHelper;
    }

    /**
     * @var array
     */
    static protected $validatedShippingMethodsCache = [];
    static protected $shippingDescriptionCache = [];

    /**
     * @return array
     */
    public function getSubProcessorClasses()
    {
        return [
            '\Xtento\OrderImport\Model\Import\Processor\Order\Address',
            '\Xtento\OrderImport\Model\Import\Processor\Order\Payment',
            '\Xtento\OrderImport\Model\Import\Processor\Order\PaymentTransaction',
            '\Xtento\OrderImport\Model\Import\Processor\Order\Item'
        ];
    }

    /**
     * @param $orderData
     *
     * @return array|bool
     */
    protected function loadCustomer(&$orderData)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = null;
        if (isset($orderData['customer_id'])) {
            if ($this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_CREATE ||
                $this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_OR_GUEST) {
                $customer = $this->customerFactory->create()->setWebsiteId($orderData['website_id'])->load($orderData['customer_id']);
            }
        }
        if ((!$customer || ($customer && !$customer->getId())) && isset($orderData['customer_email'])) {
            if ($this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_CREATE ||
                $this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_OR_GUEST) {
                $customer = $this->customerFactory->create()->setWebsiteId($orderData['website_id'])->loadByEmail($orderData['customer_email']);
            }
        }
        if (isset($orderData['customer_id']) && !$customer && isset($orderData['customer_email'])) {
            if ($this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_CREATE) {
                return [
                    'skip_processor' => true,
                    'message' => __(
                        'Customer could not be identified via email/ID in specified website/store. Try setting correct website_id/store_id. Skipping.'
                    )
                ];
            }
        }
        if ($this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_CREATE && (!$customer || !$customer->getId())) {
            // Todo, save customer and email password reset via AccountManagement->createAccountWithPasswordHash
        }
        $this->registry->register('xtento_orderimport_current_customer', $customer, true);
        if ($this->isOrderNew($orderData)) { // Do not manipulate existing orders
            if ($customer && $customer->getId()) {
                $this->customerSession->setCustomer($customer);
                if (!isset($orderData['customer_group_id'])) {
                    $orderData['customer_group_id'] = $customer->getGroupId();
                }
                $groupModel = $this->customerGroupFactory->create()->load($orderData['customer_group_id']);
                $orderData['customer_tax_class_id'] = $groupModel->getTaxClassId();
                $orderData['customer_is_guest'] = 0;
                if (!isset($orderData['customer_email'])) {
                    $orderData['customer_email'] = $customer->getEmail();
                }
            } else {
                if (!isset($orderData['customer_email'])) {
                    $host = $this->scopeConfig->getValue(\Magento\Sales\Model\AdminOrder\Create::XML_PATH_DEFAULT_EMAIL_DOMAIN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $orderData['store_id']);
                    $account = 'import_' . time() . rand(1, 10000);
                    $email = $account . '@' . $host;
                    $orderData['customer_email'] = $email;
                }
                $orderData['customer_is_guest'] = 1;
                $orderData['customer_id'] = null;
            }
        }

        return true;
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
        if (!isset($updateData['order'])) {
            return [
                'skip_processor' => true,
                'message' => __(
                    'No order data found for current row. Skipping "order" processor.'
                )
            ];
        }
        $orderData = &$updateData['order'];

        // Validate store
        if ($this->isOrderNew($orderData)) {
            $allStores = $this->storeManager->getStores();
            if (count($allStores) > 1) { // Otherwise there's just one store, that can be used.
                $storeFound = false;
                // Validate store_code
                if (array_key_exists('store_code', $orderData) && !empty($orderData['store_code'])) {
                    foreach ($allStores as $store) {
                        if ($orderData['store_code'] == $store->getCode()) {
                            $orderData['store_id'] = $store->getId();
                            if (!array_key_exists('website_id', $orderData)) {
                                $orderData['website_id'] = $this->storeManager->getStore($store->getId())->getWebsiteId();
                            }
                            unset($orderData['store_code']);
                            $storeFound = true;
                            break;
                        }
                    }
                }
                // Validate store_id
                if (array_key_exists('store_id', $orderData) && !empty($orderData['store_id'])) {
                    foreach ($allStores as $store) {
                        if ($orderData['store_id'] == $store->getId()) {
                            if (!array_key_exists('website_id', $orderData)) {
                                $orderData['website_id'] = $this->storeManager->getStore($store->getId())->getWebsiteId();
                            }
                            $storeFound = true;
                            break;
                        }
                    }
                }
                if (!$storeFound) {
                    $website = $this->storeManager->getWebsite(true);
                    $orderData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
                    $orderData['website_id'] = $website->getId();
                    /*return array(
                        'stop' => true,
                        'message' => __(
                            'No store_id or store_code set for order. This is required to import an order. Or, set a default value for one of these fields so the order can be imported into a store. Go into the mapping, and add a mapping for "store_id" and set the default value to "1" for example. (Store IDs can be found at System > Manage Stores)'
                        )
                    );*/
                }
                if (!isset($orderData['website_id'])) {
                    $orderData['website_id'] = $this->storeManager->getStore($orderData['store_id'])->getWebsiteId();
                }
            } else {
                if (!isset($orderData['store_id'])) {
                    $website = $this->storeManager->getWebsite(true);
                    $orderData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
                }
                if (!isset($orderData['website_id'])) {
                    $orderData['website_id'] = $this->storeManager->getStore($orderData['store_id'])->getWebsiteId();
                }
            }
        } else {
            if (!isset($orderData['store_id'])) {
                $website = $this->storeManager->getWebsite(true);
                $orderData['store_id'] = $website->getDefaultGroup()->getDefaultStoreId();
            }
            if (!isset($orderData['website_id'])) {
                $orderData['website_id'] = $this->storeManager->getStore($orderData['store_id'])->getWebsiteId();
            }
        }

        // Load customer
        $importCustomerResult = $this->loadCustomer($orderData);
        if ($importCustomerResult !== true) {
            return $importCustomerResult;
        }

        // Validate shipping method
        if ($this->isCreateMode($updateData) && $this->isObjectNew($updateData, 'order')) {
            $shippingMethod = isset($orderData['shipping_method']) ? $orderData['shipping_method'] : '';
            if ($shippingMethod == '') {
                // Use fallback shipping method
                $orderData['shipping_method'] = 'xtentodefaultmethod_xtentodefaultmethod';
                $orderData['shipping_description'] = __('Imported Order');
                /*return array(
                    'stop' => true,
                    'message' => __(
                        'Field shipping_method is empty for order. The field is required. You can also set a default value.'
                    )
                );*/
            }
            if (!isset($orderData['shipping_description']) || empty($orderData['shipping_description'])) {
                if (in_array($orderData['shipping_method'], self::$shippingDescriptionCache)) {
                    $orderData['shipping_description'] = self::$shippingDescriptionCache[$orderData['shipping_method']];
                } else {
                    foreach ($this->shippingConfig->getAllCarriers($orderData['store_id']) as $shippingCode => $shippingModel) {
                        $methodTitle = $shippingModel->getConfigData('title');
                        if (empty($methodTitle)) {
                            continue;
                        }
                        $carrierMethods = $shippingModel->getAllowedMethods();
                        if (!$carrierMethods) {
                            continue;
                        }
                        foreach ($carrierMethods as $methodCode => $methodTitle) {
                            if ($shippingCode . '_' . $methodCode == $orderData['shipping_method']) {
                                $orderData['shipping_description'] = $shippingModel->getConfigData('title') . ' - ' . $methodTitle;
                                self::$shippingDescriptionCache[$orderData['shipping_method']] = $orderData['shipping_description'];
                                break 2;
                            }
                        }
                    }
                }
            }

            // Check is valid shipping method
            if (!in_array($orderData['shipping_method'], self::$validatedShippingMethodsCache)) {
                $shippingCarrier = false;
                foreach ($this->shippingConfig->getAllCarriers($this->storeManager->getStore($orderData['store_id'])) as $shippingCode => $shippingModel) {
                    $carrierMethods = $shippingModel->getAllowedMethods();
                    if (!$carrierMethods) {
                        continue;
                    }
                    foreach ($carrierMethods as $methodCode => $methodTitle) {
                        if ($orderData['shipping_method'] == $shippingCode . '_' . $methodCode) {
                            $shippingCarrier = $methodTitle;
                            break 2;
                        }
                    }
                }
                // maybe: check (after item validation) if order is virtual, if not, check this
                if ($shippingCarrier === false) {
                    return [
                        'stop' => true,
                        'message' => __(
                            'The field shipping_method contains an invalid shipping method. The shipping method "%1" does not exist in this Magento installation.',
                            $orderData['shipping_method']
                        )
                    ];
                } else {
                    // Shipping method validated
                    self::$validatedShippingMethodsCache[] = $orderData['shipping_method'];
                }
            }
        }

        // Import mode validations
        // New only mode
        if ($this->getImportMode($updateData) == Import::IMPORT_MODE_NEW) {
            if (!$this->isObjectNew($updateData, 'order')) {
                return [
                    'stop' => true,
                    'message' => __(
                        'Import mode is set to "Import new only", but this order exists already.'
                    )
                ];
            }
        }
        // Delete mode
        if ($this->getImportMode($updateData) == Import::IMPORT_MODE_DELETE) {
            if ($this->isObjectNew($updateData, 'order')) {
                return [
                    'stop' => true,
                    'message' => __(
                        'Import mode is set to "Delete objects", but this order does not exist in Magento.'
                    )
                ];
            }
        }

        // Check if customer_id is supplied or if a new customer can be created
        // More validations
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $updateData
     *
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function process(&$order, &$updateData)
    {
        $orderData = &$updateData['order'];

        $this->eventManager->dispatch('xtento_orderimport_order_processor_before', ['order' => $order, 'update_data' => $updateData]);

        $warnings = [];
        $warnings = array_merge($warnings, $this->importOrderData($order, $orderData));
        $warnings = array_merge($warnings, $this->importGiftMessage($order, $orderData));
        $warnings = array_merge($warnings, $this->importStoreData($order, $orderData));
        $warnings = array_merge($warnings, $this->importRequiredData($order, $orderData));
        $warnings = array_merge($warnings, $this->importCurrencyData($order, $orderData));

        $this->eventManager->dispatch('xtento_orderimport_order_processor_after', ['order' => $order, 'update_data' => $updateData]);

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
     * @param \Magento\Sales\Model\Order $order
     * @param $orderData
     *
     * @return array
     */
    protected function importOrderData($order, $orderData)
    {
        $warnings = [];
        foreach ($orderData as $field => $value) {
            if (is_array($value)) {
                continue;
            }
            $ignoredFields = ['entity_id', 'quote_id', 'store_id', 'gift_message_id', 'gift_message'];
            if (array_key_exists($field, $ignoredFields)) {
                continue;
            }
            if ($field == 'created_at' || $field == 'updated_at') {
                // Adjust to store timezone
                //var_dump($value);
                if (version_compare($this->utilsHelper->getMagentoVersion(), '2.3.4', '>=')) {
                    $scopeDate = $this->localeDate->scopeDate(null, new \DateTime($value), true);
                } else {
                    $scopeDate = $this->localeDate->scopeDate(null, $value, true);
                }
                $offset = $scopeDate->getOffset();
                if ($offset >= 0) {
                    $scopeDate->sub(new \DateInterval('PT' . $offset . 'S'));
                } else {
                    $scopeDate->add(new \DateInterval('PT' . abs($offset) . 'S'));
                }
                $scopeDate->setTimezone(new \DateTimeZone('UTC'));
                $value = $scopeDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                //var_dump($value); die();
            }
            $order->setData($field, $value);
            if (!isset($order['base_' . $field])) {
                $order->setData('base_' . $field, $value);
            }
        }
        return $warnings;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderData
     *
     * @return array
     */
    protected function importGiftMessage($order, $orderData)
    {
        $warnings = [];
        if (isset($orderData['gift_message'])) {
            $giftMessage = $this->giftMessageFactory->create();
            $giftMessageFields = ['gift_message', 'gift_message_from', 'gift_message_to'];
            foreach ($giftMessageFields as $giftMessageField) {
                $value = (isset($orderData[$giftMessageField]) ? $orderData[$giftMessageField] : '');
                $giftMessageFieldMapped = '';
                if ($giftMessageField === 'gift_message') {
                    $giftMessageFieldMapped = 'message';
                }
                if ($giftMessageField === 'gift_message_from') {
                    $giftMessageFieldMapped = 'sender';
                }
                if ($giftMessageField === 'gift_message_to') {
                    $giftMessageFieldMapped = 'recipient';
                }
                $giftMessage->setData($giftMessageFieldMapped, (string)$value);
            }
            $giftMessage->save();
            $order->setGiftMessageId($giftMessage->getId());
        }
        return $warnings;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderData
     *
     * @return array
     */
    protected function importStoreData($order, $orderData)
    {
        $warnings = [];
        if (!$order->getRealOrderId()) {
            $order->setRealOrderId(null);
        }

        if ($this->isOrderNew($orderData)) {
            $order->setStoreId($orderData['store_id']);
        }
        return $warnings;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderData
     *
     * @return array
     */
    protected function importRequiredData($order, $orderData)
    {
        $warnings = [];
        /*if (!$order->getPayment()) {
            $fakePaymentModel = Mage::getModel('sales/order_payment');
            $fakePaymentModel->setStoreId(0)
                ->setCustomerPaymentId(0)
                ->setMethod('free');
            $order->setPayment($fakePaymentModel);
        }*/

        if ($this->isOrderNew($orderData)) {
            if (!isset($orderData['state'])) {
                $order->setData('state', 'processing');
            }
            if (!isset($orderData['status'])) {
                $order->setData('status', 'processing');
            }
        }

        if (isset($orderData['status'])) {
            // Status is in file, get state if required
            if (!isset($orderData['state'])) {
                if (!isset($this->_orderStates) || empty($this->_orderStates)) {
                    $this->_orderStates = $this->orderConfig->getStates();
                }
                // Check current order state with priority, as a status can be assigned to multiple states
                foreach ($this->_orderStates as $state => $label) {
                    if ($state == $order->getState()) {
                        foreach ($this->orderConfig->getStateStatuses($state, false) as $status) {
                            if ($status == $orderData['status']) {
                                break 2;
                            }
                        }
                        break;
                    }
                }
                // Status to set is from different state, find state and set
                foreach ($this->_orderStates as $state => $label) {
                    foreach ($this->orderConfig->getStateStatuses($state, false) as $status) {
                        if ($status == $orderData['status']) {
                            $order->setData('state', $state);
                            break 2;
                        }
                    }
                }
            }
        }

        // Rule data for price calculation
        $this->registry->unregister('rule_data');
        $productStore = $this->storeManager->getStore($order->getStoreId());
        if ($productStore) {
            $this->registry->register(
                'rule_data', new DataObject(
                [
                    'store_id' => $order->getStoreId(),
                    'website_id' => $productStore->getWebsiteId(),
                    'customer_group_id' => $order->getCustomerGroupId() ? $order->getCustomerGroupId() : 0
                ]
            )
            );
        }

        return $warnings;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $orderData
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function importCurrencyData($order, $orderData)
    {
        $warnings = [];
        if ($this->isOrderNew($orderData)) {
            $globalCurrencyCode = $this->storeManager->getWebsite(true)->getBaseCurrencyCode();
            $baseCurrency = $this->storeManager->getStore($orderData['store_id'])->getBaseCurrency();
            $quoteCurrency = $this->storeManager->getStore($orderData['store_id'])->getCurrentCurrency();

            $order->setGlobalCurrencyCode($globalCurrencyCode);
            $order->setBaseCurrencyCode($baseCurrency->getCode());
            $order->setStoreCurrencyCode($baseCurrency->getCode());
            $order->setQuoteCurrencyCode($quoteCurrency->getCode());
            $order->setStoreToBaseRate($baseCurrency->getRate($globalCurrencyCode));
            $order->setStoreToQuoteRate($baseCurrency->getRate($quoteCurrency));
            $order->setBaseToGlobalRate($baseCurrency->getRate($globalCurrencyCode));
            $order->setBaseToQuoteRate($baseCurrency->getRate($quoteCurrency));

            if (!isset($orderData['order_currency_code'])) {
                $order->setOrderCurrencyCode($baseCurrency->getCode());
            }
        }
        return $warnings;
    }
}