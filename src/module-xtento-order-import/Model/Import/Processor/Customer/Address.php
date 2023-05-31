<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-05-12T16:18:43+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Customer/Address.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor\Customer;

use Magento\Framework\Registry;

class Address extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
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
     * Address constructor.
     *
     * @param Registry $registry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Customer\Model\AddressFactory $customerAddressFactory
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        array $data = [])
    {
        parent::__construct($data);
        $this->registry = $registry;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->directoryHelper = $directoryHelper;
        $this->customerAddressFactory = $customerAddressFactory;
    }

    /**
     * @param $updateData
     *
     * @return array|bool
     */
    public function validate(&$updateData)
    {
        // Merge street lines
        if (isset($updateData['address'])) {
            foreach ($updateData['address'] as &$customerAddress) {
                $street = false;
                foreach ($customerAddress as $key => $value) {
                    if (preg_match('/^street\d$/', $key)) {
                        $street = ($street === false) ? $value : $street . "\n" . $value;
                        //unset($updateData['order_address_billing'][$key]);
                    }
                }
                if ($street !== false) {
                    $customerAddress['street'] = $street;
                }
            }
        }
        return true;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @param $updateData
     *
     * @return array|bool
     */
    public function process(&$customer, &$updateData)
    {
        $warnings = [];
        $isCustomerNew = $this->isObjectNew($updateData, 'customer');

        if (!isset($updateData['address']) || empty($updateData['address']) || !is_array($updateData['address'])) {
            return true;
        }

        foreach ($updateData['address'] as $customerAddress) {
            $warnings = array_merge($warnings, $this->importAddress($customer, $updateData['customer'], $customerAddress, $isCustomerNew));
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
     * @param $customer
     * @param $customerData
     * @param $addressData
     * @param $isCustomerNew
     *
     * @return array
     */
    protected function importAddress(&$customer, &$customerData, $addressData, $isCustomerNew)
    {
        $warnings = [];

        // Add address to customer if it doesn't exist yet
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
            $addedAddress = $this->customerAddressFactory->create();
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
                        $addedAddress->setData('old_' . $fieldName, $fieldValue);
                        break;
                    case 'customer_id':
                        $addedAddress->setCustomerId($customer->getId());
                        break;
                    default:
                        $addedAddress->setData($fieldName, $fieldValue);
                        break;
                }
            }
            $addedAddress->setCustomerId($customer->getId());
            $addedAddress->setSaveInAddressBook(1);
            try {
                $addedAddress->save();
                $customer->addAddress($addedAddress);
            } catch (\Exception $e) {
                $warnings[] = __('Exception saving customer address. Probably one of the required fields hasn\'t been populated. Exception: %1', $e->getMessage());
            } catch (\Error $e) {
                $warnings[] = __('Exception saving customer address. Probably one of the required fields hasn\'t been populated. Error: %1', $e->getMessage());
            }
        }
        if ($addedAddress !== false && isset($addressData['is_primary_billing_address']) && $addressData['is_primary_billing_address']) {
            $customer->setDefaultBilling($addedAddress->getId());
            $customer->setData('xtento_customer_billing_address_id', $addedAddress->getId());
        }
        if ($addedAddress !== false && isset($addressData['is_primary_shipping_address']) && $addressData['is_primary_shipping_address']) {
            $customer->setDefaultShipping($addedAddress->getId());
            $customer->setData('xtento_customer_shipping_address_id', $addedAddress->getId());
        }
        if ($addedAddress !== false) {
            $customer->save();
        }

        return $warnings;
    }
}