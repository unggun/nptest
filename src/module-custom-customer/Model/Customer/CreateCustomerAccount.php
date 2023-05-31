<?php
namespace Icube\CustomCustomer\Model\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\CustomerGraphQl\Model\Customer\ValidateCustomerData;
use Icube\SmsOtp\Helper\Otp as OtpHelper;
use Magento\Framework\App\ResourceConnection;
use Icube\SmsOtpGraphQl\Model\Customer\ResourceCustomer;

/**
 * Create new customer account
 */
class CreateCustomerAccount
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var SubscriptionManagerInterface
     */
    private $subscriptionManager;

    /**
     * @var ValidateCustomerData
     */
    private $validateCustomerData;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var OtpHelper
     */
    private $otpHelper;

    /**
     * @var ResourceCustomer
     */
    private $resourceCustomer;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * CreateCustomerAccount constructor.
     *
     * @param DataObjectHelper $dataObjectHelper
     * @param CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param DataObjectProcessor $dataObjectProcessor
     * @param ValidateCustomerData $validateCustomerData
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param OtpHelper $otpHelper
     * @param ResourceConnection $resource
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $accountManagement,
        SubscriptionManagerInterface $subscriptionManager,
        DataObjectProcessor $dataObjectProcessor,
        ValidateCustomerData $validateCustomerData,
        OtpHelper $otpHelper,
        ResourceCustomer $resourceCustomer,
        ResourceConnection $resource
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->subscriptionManager = $subscriptionManager;
        $this->validateCustomerData = $validateCustomerData;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->otpHelper = $otpHelper;
        $this->resourceCustomer = $resourceCustomer;
        $this->resource = $resource;
    }

    /**
     * Creates new customer account
     *
     * @param array $data
     * @param StoreInterface $store
     * @return CustomerInterface
     * @throws GraphQlInputException
     */
    public function execute(array $data, StoreInterface $store): CustomerInterface
    {
        try {
            $customer = $this->createAccount($data, $store);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        if (isset($data['is_subscribed'])) {
            if ((bool)$data['is_subscribed']) {
                $this->subscriptionManager->subscribeCustomer((int)$customer->getId(), (int)$store->getId());
            } else {
                $this->subscriptionManager->unsubscribeCustomer((int)$customer->getId(), (int)$store->getId());
            }
        }
        return $customer;
    }

    /**
     * Create account
     *
     * @param array $data
     * @param StoreInterface $store
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function createAccount(array $data, StoreInterface $store): CustomerInterface
    {
        $otpCode = $data["otp"];
        $phonenumber = $this->otpHelper->formatedPhoneNumber($data["phonenumber"]);
        $whatsappNumber =  $this->otpHelper->formatedPhoneNumber($data["whatsapp_number"]);
        $isEnableOtpRegister = $this->otpHelper->isEnableOtpRegister();
        if ($isEnableOtpRegister) {
            $isValidOtp = $this->otpHelper->isValidOtp($phonenumber, $otpCode, 'create_account');
            if ($isValidOtp == false) {
                throw new LocalizedException(__('OTP code is incorrect'));
            }

            if ($this->resourceCustomer->isPhoneNumberAlreadyRegistered($phonenumber)) {
                throw new LocalizedException(__('There is already an account with this phone number.'));
            }
        }

        $dummyEmail = $phonenumber . $this->otpHelper->getDomainEmail();
        $args['email'] = (empty($data['email'])) ? $dummyEmail : trim($data['email']);
        $data['email'] = $args['email'];
        $data['telephone'] = $phonenumber;

        $customerDataObject = $this->customerFactory->create();
        /**
         * Add required attributes for customer entity
         */
        $requiredDataAttributes = $this->dataObjectProcessor->buildOutputDataArray(
            $customerDataObject,
            CustomerInterface::class
        );
        $data = array_merge($requiredDataAttributes, $data);
        $this->validateCustomerData->execute($data);
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $data,
            CustomerInterface::class
        );
        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());

        $password = array_key_exists('password', $data) ? $data['password'] : null;
        
        if ($isEnableOtpRegister) {
            $this->otpHelper->deleteOtp($phonenumber, $otpCode, 'create_account');
        }
        
        $customer = $this->accountManagement->createAccount($customerDataObject, $password);

        if ($customer) {
            $this->setTelephoneAndWhatsapp($customer, $phonenumber, $whatsappNumber);
        }

        return $customer;
    }

    private function setTelephoneAndWhatsapp($customer, $phonenumber = null, $whatsapp = null)
    {
        if (!isset($phonenumber)) {
            throw new \Exception(_("Required parameter 'phonenumber' is missing"));
        }

        $fields[] = "telephone = '".$phonenumber."'";

        if ($this->resourceCustomer->isPhoneNumberAlreadyRegistered($phonenumber, $customer)) {
            throw new \Exception(_("There is already an account with this phone number."));
        }

        if (!isset($whatsapp)) {
            throw new \Exception(_("Required parameter 'whatsapp_number' is missing"));
        }

        $fields[] = "whatsapp_number = '".$whatsapp."'";
        
        $fields = implode(",", $fields);
        
        $connection = $this->resource->getConnection();
        $sql = "UPDATE customer_entity SET " . $fields . " where entity_id = ".$customer->getId();
        $connection->query($sql);
    }
}
