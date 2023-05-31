<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-19T15:24:26+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Order/Address.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor\Order;

use Magento\Customer\Model\Group;
use Magento\Framework\Registry;
use Xtento\OrderImport\Model\Import;

class Address extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
{
    /**
     * @var Registry 
     */
    protected $registry;

    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryHelper;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Address constructor.
     *
     * @param Registry $registry
     * @param \Magento\Sales\Model\Order\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Customer\Model\AddressFactory $customerAddressFactory
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        array $data = [])
    {
        parent::__construct($data);
        $this->registry = $registry;
        $this->addressFactory = $addressFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->directoryHelper = $directoryHelper;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->groupRepository = $groupRepository;
    }

    /**
     * @param $updateData
     *
     * @return array|bool
     */
    public function validate(&$updateData)
    {
        // Merge street lines
        if (isset($updateData['order_address_billing'])) {
            $street = false;
            foreach ($updateData['order_address_billing'] as $key => $value) {
                if (preg_match('/^street\d$/', $key)) {
                    $street = ($street === false) ? $value : $street . "\n" . $value;
                    //unset($updateData['order_address_billing'][$key]);
                }
            }
            if ($street !== false) {
                $updateData['order_address_billing']['street'] = $street;
            }
        }
        if (isset($updateData['order_address_shipping'])) {
            $street = false;
            foreach ($updateData['order_address_shipping'] as $key => $value) {
                if (preg_match('/^street\d$/', $key)) {
                    $street = ($street === false) ? $value : $street . "\n" . $value;
                    //unset($updateData['order_address_shipping'][$key]);
                }
            }
            if ($street !== false) {
                $updateData['order_address_shipping']['street'] = $street;
            }
        }

        // Check only if object is new, otherwise do not check (not required for address, not required for delete mode)
        if (!$this->isObjectNew($updateData, 'order') || !$this->isCreateMode($updateData)) {
            return true;
        } else {
            // No address in import data, try to load address from customer
            if (!isset($updateData['order_address_billing']) && !isset($updateData['order_address_shipping'])) {
                $customer = $this->registry->registry('xtento_orderimport_current_customer');
                if ($customer && $customer->getId()) {
                    $billingAddress = $customer->getPrimaryBillingAddress();
                    if ($billingAddress) {
                        $updateData['order_address_billing'] = $billingAddress->toArray();
                    }
                    $shippingAddress = $customer->getPrimaryShippingAddress();
                    if ($shippingAddress) {
                        $updateData['order_address_shipping'] = $shippingAddress->toArray();
                    }
                    if (!$shippingAddress && $billingAddress) {
                        $updateData['order_address_shipping'] = $billingAddress->toArray();

                    }
                }
            }
            // Still no address, cannot import then
            if (!isset($updateData['order_address_billing']) && !isset($updateData['order_address_shipping'])) {
                return [
                    'stop' => true,
                    'message' => __(
                        'No billing/shipping address data found for current row.'
                    )
                ];
            }
        }

        if (!isset($updateData['order_address_billing']) || empty($updateData['order_address_billing'])) {
            return [
                'stop' => true,
                'message' => __(
                    'No billing address found for current row, however, it\'s required. Please map billing address fields.'
                )
            ];
        }

        if ((!isset($updateData['order_address_shipping']) || empty($updateData['order_address_shipping']))
            && (!isset($updateData['order']['is_virtual']) || empty($updateData['order']['is_virtual']))
        ) {
            // Take it from billing adddress if no shipping address is set. For virtual orders, no shipping address is required
            $updateData['order_address_shipping'] = $updateData['order_address_billing'];
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $updateData
     *
     * @return array|bool
     */
    public function process(&$order, &$updateData)
    {
        $warnings = [];
        $isOrderNew = $this->isObjectNew($updateData, 'order');

        if (isset($updateData['order_address_billing']) && !empty($updateData['order_address_billing'])) {
            $warnings = array_merge($warnings, $this->importAddress('billing', $order, $updateData['order'], $updateData['order_address_billing'], $isOrderNew));
        }
        if (isset($updateData['order_address_shipping']) && !empty($updateData['order_address_shipping'])) {
            $warnings = array_merge($warnings, $this->importAddress('shipping', $order, $updateData['order'], $updateData['order_address_shipping'], $isOrderNew));
        }

        // Check if no address was set somehow, fall back
        if (!$order->getBillingAddress()) {
            $fakeAddressModel = $this->addressFactory->create();
            $fakeAddressModel->setStoreId(0)->setAddressType(\Magento\Quote\Model\Quote\Address::TYPE_BILLING);
            $order->setBillingAddress($fakeAddressModel);
        }

        if (!$order->getShippingAddress()) {
            $fakeAddressModel = $this->addressFactory->create();
            $fakeAddressModel->setStoreId(0)->setAddressType(\Magento\Quote\Model\Quote\Address::TYPE_SHIPPING);
            $order->setShippingAddress($fakeAddressModel);
        }

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
     * @param $addressType
     * @param \Magento\Sales\Model\Order $order
     * @param $orderData
     * @param $addressData
     * @param $isOrderNew
     */
    protected function importAddress($addressType, &$order, &$orderData, $addressData, $isOrderNew)
    {
        $warnings = [];
        $addAddress = false;
        if ($isOrderNew) {
            /* @var $orderAddress \Magento\Sales\Model\Order\Address */
            $orderAddress = $this->addressFactory->create();
            $orderAddress->setAddressType($addressType);
            $addAddress = true;

            // Create customer by email if needed
            $customerEmail = isset($orderData['customer_email']) ? $orderData['customer_email'] : '';
            $customerId = isset($orderData['customer_id']) ? $orderData['customer_id'] : 0;
            $customer = $this->registry->registry('xtento_orderimport_current_customer');
            if ($addressType == 'billing' && (!$customer || !$customer->getId()) && empty($customerEmail)) {
                $warnings[] = __('Could not create customer as customer_email field is not set.');
            }
            if ($addressType == 'billing' && !empty($customerEmail)) {
                $store = $order->getStore() ? $order->getStore() : $this->storeManager->getStore();
                /* @var $customer \Magento\Customer\Model\Customer */
                if ((!$customer || !$customer->getId()) && $this->getConfig('customer_mode') == Import::IMPORT_CUSTOMER_CREATE) {
                    $orderAddress->setData('save_in_address_book', true);
                    // Create customer
                    $firstname = isset($addressData['firstname']) ? $addressData['firstname'] : '';
                    $lastname = isset($addressData['lastname']) ? $addressData['lastname'] : '';
                    $customer = $this->customerFactory->create();
                    $customer->setStoreId($store->getId())
                        ->setWebsiteId($store->getWebsiteId())
                        ->setEmail($customerEmail)
                        ->setFirstname($firstname)
                        ->setLastname($lastname);
                    if (isset($orderData['customer_password'])) {
                        $customer->setPassword($orderData['customer_password']);
                    }
                    if (isset($orderData['customer_group_id'])) {
                        $customer->setGroupId($orderData['customer_group_id']);
                    }
                    $this->registry->unregister('xtento_orderimport_current_customer');
                    $customer->save();
                    $this->registry->register('xtento_orderimport_current_customer', $customer, true);
                }

                if ($customer && $customer->getId()) {
                    $customerId = $customer->getId();
                    $order->setCustomerId($customer->getId())
                        ->setCustomerEmail($customer->getEmail())
                        ->setCustomerIsGuest(false)
                        ->setCustomerPrefix($customer->getPrefix())
                        ->setCustomerFirstname($customer->getFirstname())
                        ->setCustomerMiddlename($customer->getMiddlename())
                        ->setCustomerLastname($customer->getLastname())
                        ->setCustomerSuffix($customer->getSuffix())
                        ->setCustomerGroupId($customer->getGroupId())
                        ->setCustomerTaxClassId($customer->getTaxClassId());
                } else {
                    $orderData['customer_id'] = null;
                    $order->setCustomerId(null);
                }
                if (isset($orderData['customer_group_id'])) {
                    $order->setCustomerGroupId($orderData['customer_group_id']);
                }
            }
            // Get customer_id for customer_email if no customer_id is set
            if ($addressType == 'billing' && !empty($customerEmail) && empty($customerId) && $this->getConfig('customer_mode') != Import::IMPORT_AS_GUEST) {
                $store = $order->getStore() ? $order->getStore() : $this->storeManager->getStore();
                $customer = $this->customerFactory->create()->setWebsiteId($store->getWebsiteId())->loadByEmail($customerEmail);
                if ($customer->getId()) {
                    $this->registry->unregister('xtento_orderimport_current_customer');
                    $this->registry->register('xtento_orderimport_current_customer', $customer, true);
                    $order->setCustomerId($customer->getId());
                } else {
                    $order->setCustomerId(null);
                    $orderData['customer_id'] = null;
                }
            }

            if (isset($addressData['country_id'])) {
                $isRegionRequired = $this->directoryHelper->isRegionRequired($addressData['country_id']);
                if ($isRegionRequired && isset($addressData['region']) && !isset($addressData['region_id'])) {
                    // Determine region_id for region
                    $regions = $this->directoryHelper->getRegionData();
                    if (isset($regions[$addressData['country_id']])) {
                        foreach ($regions[$addressData['country_id']] as $regionId => $regionData) {
                            if ($regionData['code'] == $addressData['region'] || $regionData['name'] == $addressData['region']) {
                                $addressData['region_id'] = $regionId;
                            }
                        }
                    }
                }
                if ($isRegionRequired && !isset($addressData['region']) && !isset($addressData['region_id'])) {
                    $addressData['region'] = __('---');
                }
            }

            // Add address to customer if it doesn't exist yet
            if ($customer && $customer->getId()) {
                $addedAddress = false;
                $matchingAddressFound = false;
                /** @var \Magento\Customer\Model\Address $address */
                foreach ($customer->getAddressesCollection() as $address) {
                    if (
                        (isset($addressData['firstname']) && $address->getFirstname() == $addressData['firstname']) &&
                        (isset($addressData['lastname']) && $address->getLastname() == $addressData['lastname']) &&
                        (isset($addressData['street1']) && $address->getStreetLine(1) == $addressData['street1']) &&
                        (isset($addressData['postcode']) && $address->getPostcode() == $addressData['postcode']) &&
                        (isset($addressData['city']) && $address->getCity() == $addressData['city'])
                    ) {
                        $matchingAddressFound = true;
                        $addedAddress = $address;
                        break;
                    }
                }
                if (!$matchingAddressFound && !isset($addressData['entity_id'])) {
                    $addedAddress = $this->customerAddressFactory->create();
                    foreach ($addressData as $fieldName => $value) {
                        $addedAddress->setData($fieldName, $value);
                    }
                    $addedAddress->setCustomerId($customer->getId());
                    $addedAddress->setSaveInAddressBook(1);
                    try {
                        $addedAddress->save();
                        $customer->addAddress($addedAddress);
                    } catch (\Exception $e) {
                        $reflectionObject = new \ReflectionObject($e);
                        $reflectionObjectProp = $reflectionObject->getProperty('message');
                        $reflectionObjectProp->setAccessible(true);
                        $reflectionObjectProp->setValue($e, __('Exception saving customer address. Probably one of the required fields hasn\'t been populated. Exception: %1', $e->getMessage()));
                        throw $e;
                    } catch (\Error $e) {
                        $reflectionObject = new \ReflectionObject($e);
                        $reflectionObjectProp = $reflectionObject->getProperty('message');
                        $reflectionObjectProp->setAccessible(true);
                        $reflectionObjectProp->setValue($e, __('Error saving customer address. Probably one of the required fields hasn\'t been populated. Exception: %1', $e->getMessage()));
                        throw $e;
                    }
                }
                if ($addressType == 'billing' && $addedAddress !== false && !$customer->getPrimaryBillingAddress()) {
                    $customer->setDefaultBilling($addedAddress->getId());
                }
                if ($addressType == 'shipping' && $addedAddress !== false && !$customer->getPrimaryShippingAddress()) {
                    $customer->setDefaultShipping($addedAddress->getId());
                }
                if ($addedAddress !== false) {
                    $customer->setData('xtento_customer_' . $addressType . '_address_id', $addedAddress->getId());
                    try {
                        $customer->save();
                    } catch (\Exception $e) {
                        $reflectionObject = new \ReflectionObject($e);
                        $reflectionObjectProp = $reflectionObject->getProperty('message');
                        $reflectionObjectProp->setAccessible(true);
                        $reflectionObjectProp->setValue($e, __('Exception saving customer. Probably one of the required fields hasn\'t been populated. Exception: %1', $e->getMessage()));
                        throw $e;
                    } catch (\Error $e) {
                        $reflectionObject = new \ReflectionObject($e);
                        $reflectionObjectProp = $reflectionObject->getProperty('message');
                        $reflectionObjectProp->setAccessible(true);
                        $reflectionObjectProp->setValue($e, __('Error saving customer. Probably one of the required fields hasn\'t been populated. Exception: %1', $e->getMessage()));
                        throw $e;
                    }
                }
            }

            if (!$customer || !$order->getCustomerGroupId()) {
                $order->setCustomerIsGuest(true)
                    ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID)
                    ->setCustomerTaxClassId($this->groupRepository->getById(Group::NOT_LOGGED_IN_ID)->getTaxClassId());
            }
        } else {
            $orderAddress = $order->{'get' . ucfirst($addressType) . 'Address'}();
            if (!$orderAddress) {
                $orderAddress = $this->addressFactory->create();
                $orderAddress->setAddressType($addressType);
                $addAddress = true;
            }
        }

        foreach ($addressData as $fieldName => $value) {
            if (is_array($value)) {
                continue;
            }
            $ignoredFields = ['address_id', 'parent_id', 'quote_address_id', 'customer_address_id'];
            if (array_key_exists($fieldName, $ignoredFields)) {
                continue;
            }
            $fieldValue = (string)$value;
            switch ($fieldName) {
                case 'entity_id':
                    $orderAddress->setData('old_' . $fieldName, $fieldValue);
                    break;
                case 'customer_id':
                    $orderAddress->setCustomerId($order->getCustomerId());
                    break;
                default:
                    $orderAddress->setData($fieldName, $fieldValue);
                    break;
            }
        }

        if ($addAddress) {
            $order->{'set' . ucfirst($addressType) . 'Address'}($orderAddress);
        }

        return $warnings;
    }
}