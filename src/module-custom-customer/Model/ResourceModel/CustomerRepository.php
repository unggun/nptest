<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Icube\CustomCustomer\Model\ResourceModel;

use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Framework\Exception\LocalizedException;

/**
 * Customer repository.
 *
 * CRUD operations for customer entity
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CustomerRepository implements \Icube\CustomCustomer\Api\CustomerRepositoryInterface
{
   
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Model\ResourceModel\CustomerRepositoryFactory $customerRepositoryInterface,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection,
        RestRequest $request
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->regionCollection = $regionCollection;
        $this->request = $request;
    }
    /**
     * Create or update a customer.
     * @param string $customerawpcode
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $passwordHash
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\InputException If bad input is provided
     * @throws \Magento\Framework\Exception\State\InputMismatchException If the provided email is already used
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function save($customerawpcode,\Magento\Customer\Api\Data\CustomerInterface $customer, $passwordHash = null)
    {
        $collection = $this->customerCollectionFactory->create()->addAttributeToFilter('wp_code',$customerawpcode);
        if($collection->getSize()){
            $data = $collection->getFirstItem();
            $this->request->setParam('customerId',$data->getId()); //set param customerId
            $statusJds = $customer->getCustomAttribute('status_jds')->getValue();
            $customer->setCustomAttribute('verification_status',$this->getVerificationStatus($statusJds));
            $this->setAddressRegionId($customer);
            $this->setVehicleAttribute($customer);
            return $this->customerRepositoryInterface->create()->save($customer); //use default save rest api magento
        }else{
            throw new LocalizedException(__('Customer with WP Code '.$customerawpcode.' not found'));
        }
        
    }

    protected function getVerificationStatus($statusJds){
        // register => unverified
        // data validation  => on progress
        // validated => validated
        // data validation fail => unvalidated

        $statusJds = strtolower($statusJds);
        $verificationLabel = '';

        switch ($statusJds) {
            case 'register':
                $verificationLabel = 'Unverified';
                break;
            case 'data_validation':
                $verificationLabel = 'on_progress';
                break;
            case 'validated':
                $verificationLabel = 'Validated';
                break;
            case 'data_validation_failed':
                $verificationLabel = 'Unvalidated';
                break;
            default:
                // code...
                break;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $attribute = $objectManager->create('\Magento\Eav\Model\Config')->getAttribute('customer','verification_status');
        $options    = $attribute->getSource()->getAllOptions();
        foreach ($options as $option) {
            if ($option['label'] == $verificationLabel) {
                return $option['value'];
            }
        }

        return null;
    }

    protected function setAddressRegionId(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        if (count($customer->getAddresses()) > 0) {
            $address = current($customer->getAddresses());
            $region = strtolower($address->getRegion()->getRegion());
            $regionDirectory = $this->regionCollection->create();
            /**
             * jds -> sales channel
             */
            $regionNameMapping = [
                'aceh' => 'Nanggroe Aceh Darussalam (NAD)',
                'kepulauan bangka belitung' => 'Bangka Belitung',
                'nusa tenggara barat' => 'Nusa Tenggara Barat (NTB)',
                'nusa tenggara timur' => 'Nusa Tenggara Timur (NTT)',
            ];
            $regionName = array_key_exists($region, $regionNameMapping) ? $regionNameMapping[$region] : $region;
            $regionId = $regionDirectory->addFieldToFilter('main_table.default_name', ['like' => $regionName])
                ->addFieldToFilter('main_table.country_id', 'ID')
                ->getFirstItem()->getRegionId();
            try {
                $address->setRegionId($regionId);
                $customer->setAddresses([$address]);
            } catch (\Throwable $th) {
                //
            }
        }
    }

    protected function setVehicleAttribute(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        if (count($customer->getAddresses()) > 0) {
            $address = current($customer->getAddresses());
            $roadAccess = $address->getCustomAttribute('road_access')?->getValue();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $vehicleAttribute = $objectManager->create('\Magento\Eav\Model\Config')->getAttribute('customer', 'vehicle');
            $vehicleOptions = $vehicleAttribute->getSource()->getAllOptions();
            $motorcycleOptionKey = array_search('Motorcycle', array_column($vehicleOptions, 'label'));
            $motorcycleOptionId = $vehicleOptions[$motorcycleOptionKey]['value'] ?? 0;

            if ($roadAccess == '1 Motor') {
                $vehicles = $motorcycleOptionId;
            } else {
                $vehicles = array_column($vehicleOptions, 'value');
                $vehicles = implode(',', array_filter($vehicles));
            }
            $customer->setCustomAttribute('vehicle', $vehicles);
        }
    }
}
