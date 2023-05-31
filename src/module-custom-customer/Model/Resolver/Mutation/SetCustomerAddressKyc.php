<?php
namespace Icube\CustomCustomer\Model\Resolver\Mutation;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Model\Customer as customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer as CustomerModel;

class SetCustomerAddressKyc implements ResolverInterface
{
    /**
     * @var customer
     */
    protected $customer;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DriverInterface
     */
    protected $driverInterface;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepositoryInterface;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;
    
    /**
     * @var CustomerModel
     */
    protected $customerModel;

    /**
     * @param customer $customer
     * @param CustomerFactory $customerFactory
     * @param Filesystem $filesystem
     * @param DriverInterface $driverInterface
     * @param File $file
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param AddressFactory $addressFactory
     * @param CustomerModel $customerModel
     */
    public function __construct(
        customer $customer,
        CustomerFactory $customerFactory,
        Filesystem $filesystem,
        DriverInterface $driverInterface,
        File $file,
        AddressRepositoryInterface $addressRepositoryInterface,
        AddressFactory $addressFactory,
        CustomerModel $customerModel
    ) {
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
        $this->filesystem = $filesystem;
        $this->driverInterface = $driverInterface;
        $this->file = $file;
        $this->addressRepositoryInterface = $addressRepositoryInterface;
        $this->addressFactory = $addressFactory;
        $this->customerModel = $customerModel;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $input = $args["input"];

        if (!array_key_exists("warung_name", $input)) {
            throw new GraphQlInputException(__("Warung name must be specified."));
        } else {
            if (empty($input["warung_name"]) || $input["warung_name"] == null) {
                throw new GraphQlInputException(__("Warung name can not be empty."));
            }
        }

        if (!array_key_exists("location", $input)) {
            throw new GraphQlInputException(__("Address or location must be specified."));
        } else {
            if (empty($input["location"]) || $input["location"] == null) {
                throw new GraphQlInputException(__("Location can not be empty."));
            }
        }

        if (!array_key_exists("address_note", $input)) {
            throw new GraphQlInputException(__("Address note must be specified."));
        } else {
            if (empty($input["address_note"]) || $input["address_note"] == null) {
                throw new GraphQlInputException(__("Address Note can not be empty."));
            }
        }

        if (!array_key_exists("near_store_photo", $input)) {
            throw new GraphQlInputException(__("Near store image must be specified."));
        } else {
            if (empty($input["near_store_photo"]) || $input["near_store_photo"] == null) {
                throw new GraphQlInputException(__("Near store image can not be empty."));
            }
        }

        if (!array_key_exists("road_access", $input)) {
            throw new GraphQlInputException(__("Road access must be specified."));
        } else {
            if (empty($input["road_access"]) || $input["road_access"] == null) {
                throw new GraphQlInputException(__("Road access can not be empty."));
            }
        }

        if (!array_key_exists("front_store_photo", $input)) {
            throw new GraphQlInputException(__("Front store image must be specified."));
        } else {
            if (empty($input["front_store_photo"]) || $input["front_store_photo"] == null) {
                throw new GraphQlInputException(__("Front store image can not be empty."));
            }
        }

        $fullAddress = "";
        
        if (!array_key_exists("full_address", $input)) {
            $fullAddress = "";
        } else {
            $fullAddress = $input["full_address"];
        }

        $status = "success";
        $message = "Successfully Updated";

        try {
            $customerId = (int) $context->getUserId();
            $customer = $this->customer->load($customerId);
            $customerData = $customer->getDataModel();

            $wpCode = $customerData->getCustomAttribute('wp_code');
            if (empty($wpCode) || $wpCode == "") {
                throw new GraphQlInputException(__("WP Code is empty."));
            }

            $wpValue = $wpCode->getValue();
            $customerFactory = $this->customerFactory->create();

            $customerData->setCustomAttribute("warung_name", $input["warung_name"]);
            $customer->updateData($customerData);
            $customerFactory->saveAttribute($customer, "warung_name");
            
            /* update address */
            $addressId = $customer->getDefaultShipping();
            if ($addressId == null || empty($addressId)) {
                $address = $this->addressFactory->create();
            } elseif ($addressId <> null || !empty($addressId)) {
                $address = $this->addressRepositoryInterface->getById($addressId);
            }
            $addressParam = $input["location"];
            $address->setCustomerId($customer->getEntityId())
                        ->setFirstname($customer->getFirstname())
                        ->setLastname($customer->getLastname())
                        ->setIsDefaultBilling('1')
                        ->setIsDefaultShipping('1');
                        
            if ($addressId == null || empty($addressId)) {
                $address->setAddressNote($input["address_note"]);
                $address->setRoadAccess($input["road_access"]);
                $address->setFullAddress($fullAddress);
                $address->setCompany($input["warung_name"]);
                $address->setLongitude($addressParam["longitude"]);
                $address->setLatitude($addressParam["latitude"]);
                $address->setVillage($addressParam["district"]);
                $address->setDistrict($addressParam["sub_district"]);
            } elseif ($addressId <> null || !empty($addressId)) {
                $address->setCustomAttribute("address_note", $input["address_note"]);
                $address->setCustomAttribute("road_access", $input["road_access"]);
                $address->setCustomAttribute("full_address", $fullAddress);
                $address->setCustomAttribute("longitude", $addressParam["longitude"]);
                $address->setCustomAttribute("latitude", $addressParam["latitude"]);
                $address->setCustomAttribute("village", $addressParam["district"]);
                $address->setCustomAttribute("district", $addressParam["sub_district"]);
                $address->setCompany($input["warung_name"]);
            }

            if (array_key_exists("country_code", $addressParam)) {
                $address->setCountryId($addressParam["country_code"]);
            }

            if (array_key_exists("region", $addressParam)) {
                $address->setRegionId($addressParam["region"]["region_id"]);
            }

            if (array_key_exists("postcode", $addressParam)) {
                $address->setPostcode($addressParam["postcode"]);
            }

            if (array_key_exists("city", $addressParam)) {
                $address->setCity($addressParam["city"]);
            }

            if (array_key_exists("telephone", $addressParam)) {
                $address->setTelephone($addressParam["telephone"]);
            }

            if (array_key_exists("street", $addressParam)) {
                $address->setStreet($addressParam["street"]);
            }

            /* end update address */

            if (array_key_exists("referral_code", $input)) {
                if ($input["referral_code"] <> null || !empty($input["referral_code"])) {
                    $customerData->setCustomAttribute("referral_code", $input["referral_code"]);
                    $customer->updateData($customerData);
                    $customerFactory->saveAttribute($customer, "referral_code");
                }
            }

            $dirNearStore = $this->filesystem
                                 ->getDirectoryRead(DirectoryList::MEDIA)
                                 ->getAbsolutePath("customer_address");
            $dirFrontStore = $this->filesystem
                                  ->getDirectoryRead(DirectoryList::MEDIA)
                                  ->getAbsolutePath("customer_address");

            if (!$this->driverInterface->isDirectory($dirNearStore)) {
                $this->file->mkdir($dirNearStore, 0775);
            }

            if (!$this->driverInterface->isDirectory($dirFrontStore)) {
                $this->file->mkdir($dirFrontStore, 0775);
            }

            $dataNearPhoto = explode(",", $input["near_store_photo"]);
            $dataFrontPhoto = explode(",", $input["front_store_photo"]);

            switch ($dataNearPhoto[0]) {
                case "data:image/jpeg;base64":
                    $extNearPhoto = ".jpg";
                    break;
                case "data:image/jpg;base64":
                    $extNearPhoto = ".jpg";
                    break;
                case "data:image/png;base64":
                    $extNearPhoto = ".png";
                    break;
                default:
                    $extNearPhoto = ".default";
            }
            if ($extNearPhoto == ".default") {
                throw new GraphQlInputException(__('"Near store photo File Format not supported.'));
            }

            switch ($dataFrontPhoto[0]) {
                case "data:image/jpeg;base64":
                    $extFrontPhoto = ".jpg";
                    break;
                case "data:image/jpg;base64":
                    $extFrontPhoto = ".jpg";
                    break;
                case "data:image/png;base64":
                    $extFrontPhoto = ".png";
                    break;
                default:
                    $extFrontPhoto = ".default";
            }
            if ($extFrontPhoto == ".default") {
                throw new GraphQlInputException(__('"Front store photo File Format not supported.'));
            }

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $dataNearPhoto = base64_decode($dataNearPhoto[1]);
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $dataFrontPhoto = base64_decode($dataFrontPhoto[1]);
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage

            $fileDir = $this->getDispersionPath($wpCode->getValue()) . "/";

            if (!$this->driverInterface->isDirectory($dirNearStore . $fileDir)) {
                $this->file->mkdir($dirNearStore . $fileDir, 0775);
            }

            if (!$this->driverInterface->isDirectory($dirFrontStore . $fileDir)) {
                $this->file->mkdir($dirFrontStore . $fileDir, 0775);
            }

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_put_contents(
                $dirNearStore . $fileDir . $wpCode->getValue() . "_nearstore" . $extNearPhoto,
                $dataNearPhoto
            )) {
                $status = "success";
                $message = "Successfully Updated";

                if ($addressId == null || empty($addressId)) {
                    $address->setNearStorePhoto($fileDir . $wpCode->getValue() . "_nearstore" . $extNearPhoto);
                } elseif ($addressId <> null || !empty($addressId)) {
                    $address->setCustomAttribute(
                        "near_store_photo",
                        $fileDir . $wpCode->getValue() . "_nearstore" . $extNearPhoto
                    );
                }
                
            } else {
                $status = "failed";
                $message = "Error when saving Near store photo.";
            }
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_put_contents(
                $dirFrontStore . $fileDir . $wpCode->getValue() . "_frontstore"  . $extFrontPhoto,
                $dataFrontPhoto
            )) {
                $status = "success";
                $message = "Successfully Updated";
                
                if ($addressId == null || empty($addressId)) {
                    $address->setFrontStorePhoto($fileDir . $wpCode->getValue() . "_frontstore" . $extFrontPhoto);
                } elseif ($addressId <> null || !empty($addressId)) {
                    $address->setCustomAttribute(
                        "front_store_photo",
                        $fileDir . $wpCode->getValue() . "_frontstore" . $extFrontPhoto
                    );
                }
            } else {
                $status = "failed";
                $message = "Error when saving Front store photo";
            }
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
            if ($addressId == null || empty($addressId)) {
                $address->save();
            } elseif ($addressId <> null || !empty($addressId)) {
                $this->addressRepositoryInterface->save($address);
            }

            $customerModel = $this->customerModel->load($customer->getId());
            $customerData = $customerModel->getDataModel();
            $customerData->setCustomAttribute('verification_status', "on_progress");
            $customerModel->updateData($customerData);
            $customerFactory = $this->customerFactory->create();
            $customerFactory->saveAttribute($customerModel, "verification_status");
            
            $return = [
                "status" => $status,
                "message" => $message
            ];
            return $return;
        } catch (Exception $e) {
            $this->logger($e->getMessage());
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    private function logger($message)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("Icube\CustomCustomer\Model\Resolver\Mutation\SetCustomerAddressKyc::resolve " . $message);
    }

    private function getDispersionPath($fileName)
    {
        $char = 0;
        $dispersionPath = '';
        while ($char < 2 && $char < strlen($fileName)) {
            if (empty($dispersionPath)) {
                $dispersionPath = '/' . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            } else {
                $dispersionPath = $this->addDirSeparator(
                    $dispersionPath
                ) . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            }
            $char++;
        }
        return $dispersionPath;
    }
    
    private function addDirSeparator($dir)
    {
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        return $dir;
    }
}
