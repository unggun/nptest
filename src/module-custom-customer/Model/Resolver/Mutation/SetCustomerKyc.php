<?php
namespace Icube\CustomCustomer\Model\Resolver\Mutation;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\Io\File;

class SetCustomerKyc implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var CustomerModel
     */
    protected $customerModel;

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
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerModel $customerModel
     * @param CustomerFactory $customerFactory
     * @param Filesystem $filesystem
     * @param DriverInterface $driverInterface
     * @param File $file
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerModel $customerModel,
        CustomerFactory $customerFactory,
        Filesystem $filesystem,
        DriverInterface $driverInterface,
        File $file
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerModel = $customerModel;
        $this->customerFactory = $customerFactory;
        $this->filesystem = $filesystem;
        $this->driverInterface = $driverInterface;
        $this->file = $file;
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

        if (!array_key_exists("customer_name", $args["input"])) {
            throw new GraphQlInputException(__("Customer name must be specified."));
        }

        if (!array_key_exists("no_ktp", $args["input"])) {
            throw new GraphQlInputException(__("KTP number must be specified."));
        }

        if (!array_key_exists("ktp_image", $args["input"])) {
            throw new GraphQlInputException(__("KTP image must be specified."));
        }

        if (!array_key_exists("ktp_selfie", $args["input"])) {
            throw new GraphQlInputException(__("KTP selfie image must be specified."));
        }

        if (!array_key_exists("agreement", $args["input"])) {
            throw new GraphQlInputException(__("Agreement must be specified."));
        }

        foreach ($args["input"] as $key => $val) {
            if ($val == null || empty($val)) {
                throw new GraphQlInputException(__($key . " can not be empty"));
            }
        }

        $status = "success";
        $message = "Successfully Updated";

        try {
            $customerId = (int) $context->getUserId();
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $customerName = $customer->getFirstName() . " " . $customer->getLastName();
            if ($customerName <> $args["input"]["customer_name"]) {
                throw new GraphQlInputException(__("Customer not authorized."));
            }

            $customerModel = $this->customerModel->load($customer->getId());
            $customerData = $customerModel->getDataModel();
            $wpCode = $customerData->getCustomAttribute('wp_code');
            if (empty($wpCode) || $wpCode == "") {
                throw new GraphQlInputException(__("WP Code is empty."));
            }
            $customerData->setCustomAttribute('ktp_number', $args["input"]["no_ktp"]);
            $customerModel->updateData($customerData);
            $customerFactory = $this->customerFactory->create();
            $customerFactory->saveAttribute($customerModel, "ktp_number");
            
            $dirKtp = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath("customer");
            $dirSelfie = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath("customer");

            if (!$this->driverInterface->isDirectory($dirKtp)) {
                $this->file->mkdir($dirKtp, 0775);
            }

            if (!$this->driverInterface->isDirectory($dirSelfie)) {
                $this->file->mkdir($dirSelfie, 0775);
            }

            $dataImageKtp = explode(",", $args["input"]["ktp_image"]);
            $dataSelfieKtp = explode(",", $args["input"]["ktp_selfie"]);

            switch ($dataImageKtp[0]) {
                case "data:image/jpeg;base64":
                    $extImageKtp = ".jpg";
                    break;
                case "data:image/jpg;base64":
                    $extImageKtp = ".jpg";
                    break;
                case "data:image/png;base64":
                    $extImageKtp = ".png";
                    break;
                default:
                    $extImageKtp = ".default";
            }
            if ($extImageKtp == ".default") {
                throw new GraphQlInputException(__('"Image KTP File Format not supported.'));
            }

            switch ($dataSelfieKtp[0]) {
                case "data:image/jpeg;base64":
                    $extSelfieKtp = ".jpg";
                    break;
                case "data:image/jpg;base64":
                    $extSelfieKtp = ".jpg";
                    break;
                case "data:image/png;base64":
                    $extSelfieKtp = ".png";
                    break;
                default:
                    $extSelfieKtp = ".default";
            }
            if ($extSelfieKtp == ".default") {
                throw new GraphQlInputException(__('"Selfie Image KTP File Format not supported.'));
            }
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $dataImageKtp = base64_decode($dataImageKtp[1]);
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $dataSelfieKtp = base64_decode($dataSelfieKtp[1]);
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage

            $fileDir = $this->getDispersionPath($wpCode->getValue()) . "/";

            if (!$this->driverInterface->isDirectory($dirKtp . $fileDir)) {
                $this->file->mkdir($dirKtp . $fileDir, 0775);
            }

            if (!$this->driverInterface->isDirectory($dirSelfie . $fileDir)) {
                $this->file->mkdir($dirSelfie . $fileDir, 0775);
            }

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_put_contents($dirKtp . $fileDir . $wpCode->getValue() . "_ktp" . $extImageKtp, $dataImageKtp)) {
                $status = "success";
                $message = "Successfully Updated";
                
                $customerData = $customerModel->getDataModel();
                $customerData->setCustomAttribute(
                    'path_image_ktp',
                    $fileDir . $wpCode->getValue() . "_ktp" . $extImageKtp
                );
                $customerModel->updateData($customerData);
                $customerFactory->saveAttribute($customerModel, "path_image_ktp");
            } else {
                $status = "failed";
                $message = "Error when saving Image KTP";
            }
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
            
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_put_contents(
                $dirSelfie . $fileDir . $wpCode->getValue() . "_ktpselfie"  . $extSelfieKtp,
                $dataSelfieKtp
            )) {
                $status = "success";
                $message = "Successfully Updated";

                $customerData = $customerModel->getDataModel();
                $customerData->setCustomAttribute(
                    'path_selfie_ktp',
                    $fileDir . $wpCode->getValue() . "_ktpselfie" . $extSelfieKtp
                );
                $customerModel->updateData($customerData);
                $customerFactory->saveAttribute($customerModel, "path_selfie_ktp");
            } else {
                $status = "failed";
                $message = "Error when saving Selfie KTP";
            }
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage

            $agreement = $args["input"]["agreement"] ? 1 : 0;
            $customerData = $customerModel->getDataModel();
            $customerData->setCustomAttribute('is_agree', $agreement);
            $customerModel->updateData($customerData);
            $customerFactory->saveAttribute($customerModel, "is_agree");

            $return = [
                "status" => $status,
                "message" => $message
            ];
        } catch (Exception $e) {
            $this->logger($e->getMessage());
            throw new GraphQlInputException(__($this->logger($e->getMessage())));
        }
        return $return;
    }

    /**
     * @inheritdoc
     */
    private function logger($message)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("Icube\CustomCustomer\Model\Resolver\Mutation\SetCustomerKyc::resolve " . $message);
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
