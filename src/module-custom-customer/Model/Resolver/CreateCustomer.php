<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\CustomCustomer\Model\Resolver;

use Icube\CustomerGraphQl\Helper\EmailConfirmation;
use Icube\CustomCustomer\Model\Customer\CreateCustomerAccount;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Newsletter\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Icube\SmsOtp\Helper\Otp as OtpHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Create customer account resolver
 */
class CreateCustomer extends \Icube\CustomerGraphQl\Model\Resolver\CreateCustomer
{
    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var CreateCustomerAccount
     */
    private $createCustomerAccount;

    /**
     * @var Config
     */
    private $newsLetterConfig;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var EmailConfirmation
     */
    protected $helperConfirmation;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var CustomerModel
     */
    protected $customerModel;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    
    /**
     * @var OtpHelper
     */
    private $otpHelper;
    
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * CreateCustomer constructor.
     *
     * @param ExtractCustomerData $extractCustomerData
     * @param CreateCustomerAccount $createCustomerAccount
     * @param Config $newsLetterConfig
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param EmailConfirmation $helperConfirmation
     * @param AddressInterfaceFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerModel $customerModel
     * @param CustomerFactory $customerFactory
     * @param OtpHelper $otpHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        ExtractCustomerData $extractCustomerData,
        CreateCustomerAccount $createCustomerAccount,
        Config $newsLetterConfig,
        CustomerTokenServiceInterface $customerTokenService,
        EmailConfirmation $helperConfirmation,
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerModel $customerModel,
        CustomerFactory $customerFactory,
        OtpHelper $otpHelper,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->newsLetterConfig = $newsLetterConfig;
        $this->extractCustomerData = $extractCustomerData;
        $this->createCustomerAccount = $createCustomerAccount;
        $this->customerTokenService = $customerTokenService;
        $this->helperConfirmation = $helperConfirmation;
        $this->addressFactory = $addressFactory;
        $this->addressRepository  = $addressRepository;
        $this->customerModel = $customerModel;
        $this->customerFactory = $customerFactory;
        $this->otpHelper = $otpHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if (!$this->newsLetterConfig->isActive(ScopeInterface::SCOPE_STORE)) {
            $args['input']['is_subscribed'] = false;
        }
        if (isset($args['input']['date_of_birth'])) {
            $args['input']['dob'] = $args['input']['date_of_birth'];
        }

        $customer = $this->createCustomerAccount->execute(
            $args['input'],
            $context->getExtensionAttributes()->getStore()
        );

        $province = $args['input']['customer_address'][0]['province'];
        $city = $args['input']['customer_address'][0]['county_town'];
        $postcode = null;
        $regionId = $this->getRegionId($province);
        $postcode = $this->getPostcodeByCity($city, $regionId);
        
        $wpCode = "WP" . $postcode . "1" . $customer->getId();

        $customerModel = $this->customerModel->load($customer->getId());
        $customerData = $customerModel->getDataModel();
        $customerData->setCustomAttribute('wp_code', $wpCode);
        $customerModel->updateData($customerData);
        $customerFactory = $this->customerFactory->create();
        $customerFactory->saveAttribute($customerModel, "wp_code");

        $customerModel = $this->customerModel->load($customer->getId());
        $customerData = $customerModel->getDataModel();
        $customerData->setCustomAttribute('verification_status', "unverified");
        $customerModel->updateData($customerData);
        $customerFactory = $this->customerFactory->create();
        $customerFactory->saveAttribute($customerModel, "verification_status");

        $customerModel = $this->customerModel->load($customer->getId());
        $customerData = $customerModel->getDataModel();
        $customerData->setCustomAttribute('status_jds', "registered");
        $customerModel->updateData($customerData);
        $customerFactory = $this->customerFactory->create();
        $customerFactory->saveAttribute($customerModel, "status_jds");
        
        $customerRepo = $this->customerRepositoryInterface->getById($customer->getId());
        $customerRepo->setEmail($wpCode . $this->otpHelper->getDomainEmail());
        $this->customerRepositoryInterface->save($customerRepo);
        
        /* save address of customer */
        $argsAddress = $args['input']['customer_address'][0] ?? [
            'county_town' => '',
            'province' => '',
        ];
        
        $dataCity = [
            "city" => $argsAddress["county_town"],
            "province" => $argsAddress["province"],
            "firstname" => $customer->getFirstname(),
            "lastname" => $customer->getLastname(),
            "customer_id" => $customer->getId(),
            "phoneNumber" => $args["input"]["phonenumber"],
        ];

        $this->savingAddress($dataCity);

        $data = $this->extractCustomerData->execute($customer);

        $email = $data['email'];
        $password = $args['input']['password'];
        $token = '';

        $sendEmail = $this->helperConfirmation->accountConfirmation($email);
        if ($sendEmail == false) {
            $token = $this->customerTokenService->createCustomerAccessToken($email, $password);
        }

        return [
            'customer' => $data,
            'token' => $token
        ];
    }

    /**
     * It saves the customer's shipping/billing address
     *
     * @param data an array of data that will be used to create the address.
     *
     * @return void
     */
    private function savingAddress($data = [])
    {
        $street[] = $data["city"] . ", " . $data["province"];
        $regionId = $this->getRegionId($data["province"]);
        $postcode = $this->getPostcodeByCity($data["city"], $regionId);

        $address = $this->addressFactory->create();
        $address->setFirstname($data["firstname"]);
        $address->setLastname($data["lastname"]);
        $address->setTelephone($data["phoneNumber"]);
        $address->setStreet($street);
        $address->setCity($data["city"]);
        $address->setPostcode($postcode);
        $address->setCountryId("ID");
        $address->setRegionId($regionId);
        $address->setIsDefaultShipping(1);
        $address->setIsDefaultBilling(1);
        $address->setCustomerId($data["customer_id"]);

        try {
            $this->addressRepository->save($address);
        } catch (\Exception $e) {
            $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/Icube_CustomAddress.log');
            $logger = new \Laminas\Log\Logger();
            $logger->addWriter($writer);
            $logger->info("=============");
            $logger->info("START");
            $logger->info($e->getMessage());
            $logger->info($data);
            $logger->info("END");
            $logger->info("-------------");
        }
    }

    /**
     * It's a function to get postcode by city name.
     * @param String $city
     * @param Integer $regionId
     * @return String postcode default 99999
     */
    private function getPostcodeByCity($city, $regionId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('city');

        $sql = $connection->select()
            ->from($tableName, ["city", "postcode"])
            ->where("city = '$city' and region_id = $regionId");

        $result = $connection->fetchAll($sql);

        $postcode = "99999";
        if (count($result) > 0) {
            $postcode = $result[0]["postcode"];
        }

        return $postcode;
    }

    /**
     * It takes a province name as a parameter and returns the region ID of that province
     *
     * @param province The province name
     *
     * @return Int The region id of the province.
     */
    private function getRegionId($province)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('directory_country_region');

        $sql = $connection->select()
            ->from($tableName, ["region_id", "code"])
            ->where("default_name = '$province'");

        $result = $connection->fetchAll($sql);

        $regionId = 0;
        if (count($result) > 0) {
            $regionId = $result[0]["region_id"];
        }

        return $regionId;
    }
}
