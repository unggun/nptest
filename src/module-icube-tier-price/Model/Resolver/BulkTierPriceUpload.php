<?php

declare(strict_types=1);

namespace Icube\TierPrice\Model\Resolver;

use Icube\TierPrice\Helper\TierPriceHelper;
use Icube\TierPrice\Model\TierPriceFactory;
use Magento\Catalog\Api\Data\TierPriceInterfaceFactory;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexerProcessor;
use Magento\Catalog\Model\Product\Price\TierPricePersistence;
use Magento\Catalog\Model\Product\Price\Validation\TierPriceValidator;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductIdLocatorInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

use function Safe\base64_decode;

class BulkTierPriceUpload implements ResolverInterface
{
    const CUSTOMER_TYPE_ERP = 'erp';
    const CUSTOMER_TYPE_EMAIL = 'email';
    const CUSTOMER_TYPE_PERCENTAGE = 'percentage';

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var PriceIndexerProcessor
     */
    private $priceIndexProcessor;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductIdLocatorInterface
     */
    private $productIdLocator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;

    /**
     * @var TierPriceFactory
     */
    private $tierPriceFactory;

    /**
     * @var TierPriceHelper
     */
    private $tierPriceHelper;

    /**
     * @var TierPriceInterfaceFactory
     */
    private $tierPriceInterfaceFactory;

    /**
     * @var TierPricePersistence
     */
    private $tierPricePersistence;

    /**
     * @var TierPriceValidator
     */
    private $tierPriceValidator;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $attachmentUrl;

    private $errorLog;

    /**
     * @param AuthorizationInterface $authorization
     * @param PriceIndexerProcessor $priceIndexProcessor
     * @param TierPriceValidator $tierPriceValidator
     * @param ProductIdLocatorInterface $productIdLocator
     * @param TierPricePersistence $tierPricePersistence
     * @param TierPriceFactory $tierPriceFactory
     * @param TierPriceInterfaceFactory $tierPriceInterfaceFactory
     * @param CustomerFactory $customerFactory
     * @param DirectoryList $directoryList
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param TierPriceHelper $tierPriceHelper
     * @param TimezoneInterface $timezoneInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        AuthorizationInterface $authorization,
        PriceIndexerProcessor $priceIndexProcessor,
        TierPriceValidator $tierPriceValidator,
        ProductIdLocatorInterface $productIdLocator,
        TierPricePersistence $tierPricePersistence,
        TierPriceFactory $tierPriceFactory,
        TierPriceInterfaceFactory $tierPriceInterfaceFactory,
        CustomerFactory $customerFactory,
        DirectoryList $directoryList,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManagerInterface,
        TierPriceHelper $tierPriceHelper,
        TimezoneInterface $timezoneInterface,
        LoggerInterface $logger
    ) {
        $this->authorization = $authorization;
        $this->priceIndexProcessor = $priceIndexProcessor;
        $this->tierPriceValidator = $tierPriceValidator;
        $this->productIdLocator = $productIdLocator;
        $this->tierPricePersistence = $tierPricePersistence;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->tierPriceInterfaceFactory = $tierPriceInterfaceFactory;
        $this->customerFactory = $customerFactory;
        $this->directoryList = $directoryList;
        $this->productFactory = $productFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->tierPriceHelper = $tierPriceHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->logger = $logger;
    }

    /**
     * Resolver Upsert Tier Price
     *
     * @param Field $field
     * @param [type] $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return void
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $file = $args['input']['file'];
        $isAllow = $this->authorization->isAllowed('Icube_CatalogGraphQl::tierprice');
        $output = [];

        if (!$isAllow) {
            throw new GraphQlAuthorizationException(__('Token invalid'));
        }

        if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $file)) {
            throw new GraphQlInputException(__('Invalid base64 encode'));
        }

        $this->createErrorLog();

        $preparationData = $this->preparationData($file, $args['input']['email_user'], $args['input']['vendor_code'] ?? '');

        $row = 2;
        foreach ($preparationData as $idx => $data) {
            if ($idx !== 'failed') {
                try {
                    $tierPriceExist = $this->tierPriceHelper->getTierPriceUploadCSV($data['customerId'], $data['vendor_code'], $data['customer_group_id'], $data['product_sku'], $data['step_qty'], $data['apply_to_price']);
                    if ($tierPriceExist->getTierDiscountId()) {
                        //update
                        $tierPriceExist->setErpPromoId($data['erp_promo_id']);
                        $tierPriceExist->setCreator($data['creator']);
                        $tierPriceExist->setDiscountPercentage($data['discount_percentage']);
                        $tierPriceExist->setDiscountAmount($data['discount_amount']);
                        $tierPriceExist->setStartDate($data['start_date']);
                        $tierPriceExist->setEndDate($data['end_date']);
                        $tierPriceExist->setApplyToPrice($data['apply_to_price']);
                        if ($data['apply_to_price'] == 1) {
                            $tierPriceExist->setStepQty(1);
                        }
                        $tierPriceExist->setTierPriceId($tierPriceExist->getTierDiscountId());
                        $tierPriceExist->save();
                    } else {
                        //insert
                        $obj = $this->tierPriceFactory->create();
                        $obj->setErpPromoId($data['erp_promo_id']);
                        $obj->setErpId($data['erp_id']);
                        $obj->setVendorCode($data['vendor_code']);
                        $obj->setCustomerGroupId($data['customer_group_id']);
                        $obj->setCustomerId($data['customer_id']);
                        $obj->setProductSku($data['product_sku']);
                        $obj->setStepQty($data['step_qty']);
                        $obj->setDiscountPercentage($data['discount_percentage']);
                        $obj->setDiscountAmount($data['discount_amount']);
                        $obj->setStartDate($data['start_date']);
                        $obj->setEndDate($data['end_date']);
                        $obj->setCreator($data['creator']);
                        $obj->setApplyToPrice($data['apply_to_price']);
                        $obj->save();
                    }

                    $preparationData['failed']['message']['rows_success']++;
                } catch (\Throwable $e) {
                    $this->errorLog->info('Error,' . $row . ',"' . __($e->getMessage()) . '"');

                    $preparationData['failed']['parameters'][] = [
                        'message' => $e->getMessage(),
                        'row' => $row
                    ];

                    $preparationData['failed']['message']['rows_error']++;
                    $preparationData['failed']['message']['rows_not_changed']++;
                    $preparationData['failed']['message']['rows_processed']++;
                } catch (\Exception $e) {
                    $this->errorLog->info('Error,' . $row . ',"' . __($e->getMessage()) . '"');

                    $preparationData['failed']['parameters'][] = [
                        'message' => $e->getMessage(),
                        'row' => $row
                    ];

                    $preparationData['failed']['message']['rows_error']++;
                    $preparationData['failed']['message']['rows_not_changed']++;
                    $preparationData['failed']['message']['rows_processed']++;
                }
            }

            $row++;
        }

        if (isset($preparationData['failed'])) {
            $status = 'success';

            if ($preparationData['failed']['message']['rows_found'] === $preparationData['failed']['message']['rows_errors']) {
                $status = 'failed';
            } elseif ($preparationData['failed']['message']['rows_found'] !== $preparationData['failed']['message']['rows_success']) {
                $status = 'warning';
            }

            $output = [
                'attachment_url' => $this->attachmentUrl,
                'is_success' => true,
                'status' => $status,
                'message' => json_encode($preparationData['failed']['message'])
            ];
        }

        return $output;
    }

    /**
     * Create error log
     */
    private function createErrorLog()
    {
        // Set log name
        $time = time();
        $logName = 'ProductTierPriceUpload-' . $time . '.csv';
        $logPath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'ProductTierPrice' . DIRECTORY_SEPARATOR;

        // Create directory if not exist
        !is_dir($logPath) && mkdir($logPath, 0755, true);

        // Initiate log
        $writer = new \Laminas\Log\Writer\Stream($logPath . $logName);
        $this->errorLog = new \Laminas\Log\Logger();
        $this->errorLog->addWriter($writer);
        $this->errorLog->info('Type,Line,Message');

        // Create and set attachment URL
        $attachmentUrl = $this->storeManagerInterface->getStore()->getBaseUrl() . 'custom_tierprice/import/downloadLog/file/' . $time;
        $this->attachmentUrl = $attachmentUrl;
    }

    private function getLines($file)
    {
        $b64 = base64_decode($file, true);
        $lines = explode(PHP_EOL, $b64);

        return $lines;
    }

    /**
     * Detect Delimiter
     *
     * @param string $firstLine
     * @return string
     */
    private function detectDelimiter($firstLine)
    {
        $delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

        foreach ($delimiters as $delimiter => &$count) {
            $count = count(str_getcsv($firstLine, $delimiter));
        }

        return array_search(max($delimiters), $delimiters);
    }

    /**
     * Prepare Data Import
     *
     * @param string $file
     * @param string $creator
     * @param string $vendorCode
     * @return array
     */
    public function preparationData(string $file, string $creator, string $vendorCode = ''): array
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $b64 = base64_decode($file, true);
        $lines = explode(PHP_EOL, $b64);
        $delimiter = $this->detectDelimiter($lines[0]);
        $column = [];
        $parameters = [];
        $mapData = [];

        // Prepare Error Data
        $message = [
            'rows_errors' => 0,
            'rows_found' => count($lines) - 1,
            'rows_not_changed' => 0,
            'rows_processed' => 0,
            'rows_success' => 0
        ];

        foreach ($lines as $key => $line) {
            if ($key === 0) {
                foreach (str_getcsv($line, $delimiter) as $i => $name) {
                    $column[$name] = $i;
                }
                continue;
            }

            //skip if row is empty
            if ($line == '') {
                continue;
            }

            $header = str_getcsv($line, $delimiter);
            /* check mandatory columns */
            $requiredColumns = ["customer_type","customer_value","customer_group","sku","step_qty","discount_type","discount_value","start_date","end_date"];
            $missingColumns = array_diff_key(array_flip($requiredColumns), $column);

            if (count($missingColumns) > 0 && $key === 1) {
                $missingColumn = implode(', ', array_flip($missingColumns));

                if (count($missingColumns) > 1) {
                    $this->errorLog->info('Error,1,"' . __("Required columns: %1 not found!", $missingColumn) . '"');
                } else {
                    $this->errorLog->info('Error,1,"' . __("Required column: %1 not found!", $missingColumn) . '"');
                }

                $parameters[] = [
                    'message' => count($missingColumns) > 1 ? __("Required columns: %1 not found!", $missingColumn) : __("Required column: %1 not found!", $missingColumn),
                    'row' => 1,
                    'columns' => array_keys($missingColumns)
                ];
            }
        }

        if (empty($parameters)) {
            foreach ($lines as $key => $line) {
                if ($key === 0) {
                    foreach (str_getcsv($line, $delimiter) as $i => $name) {
                        $column[$name] = $i;
                    }
                    continue;
                }

                //skip if row is empty
                if ($line == '') {
                    $message['rows_found']--;
                    continue;
                }

                $header = str_getcsv($line, $delimiter);
                if (count($header)==1) {
                    $header = explode(",", $header[0]);
                }

                $discountType = str_replace('"', "", $header[$column['discount_type']]);
                $discountValue = str_replace('"', "", $header[$column['discount_value']]);
                $customerType = str_replace('"', "", $header[$column['customer_type']]);
                $customerValue = str_replace('"', "", $header[$column['customer_value']]);
                $customerGroup = str_replace('"', "", $header[$column['customer_group']]);
                $productSku = str_replace('"', "", $header[$column['sku']]);
                $stepQty = str_replace('"', "", $header[$column['step_qty']]);
                $startDate = str_replace('"', "", $header[$column['start_date']]);
                $endDate = str_replace('"', "", $header[$column['end_date']]);

                $erpPromoId = '';
                $applyToPrice = 0;
                if (isset($column['erp_promo_id'])) {
                    $erpPromoId = str_replace('"', "", $header[$column['erp_promo_id']]);
                }
                if (isset($column['apply_to_price'])) {
                    $applyToPrice = str_replace('"', "", $header[$column['apply_to_price']]);
                }
                if (isset($column['vendor_code'])) {
                    $vendorCode = str_replace('"', "", $header[$column['vendor_code']]);
                }

                /* check required values */
                $valuesEmpty = $this->checkRequiredValues($customerType, $customerValue, $customerGroup, $productSku, $stepQty, $discountType, $discountValue);
                if (!empty($valuesEmpty)) {
                    $missingValues = implode(', ', $valuesEmpty);

                    if (count($valuesEmpty) > 1) {
                        $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Required values: %1 are empty!", $missingValues) . '"');
                    } else {
                        $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Required value: %1 is empty!", $missingValues) . '"');
                    }

                    $parameters[] = [
                        'message' => count($valuesEmpty) > 1 ? __("Required values: %1 are empty!", $missingValues) : __("Required value: %1 is empty!", $missingValues),
                        'row' => $key + 1,
                        'columns' => $valuesEmpty
                    ];

                    $message['rows_errors']++;
                    $message['rows_not_changed']++;
                    $message['rows_processed']++;

                    continue;
                }

                /* check format values */
                $invalidFormat = $this->checkFormatValues($customerType, $stepQty, $discountType, $discountValue, $startDate, $endDate, $applyToPrice);
                if (!empty($invalidFormat)) {
                    $invalidColumnFormat = implode(', ', $invalidFormat);

                    if (count($invalidFormat) > 1) {
                        $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Invalid format values: %1!", $invalidColumnFormat) . '"');
                    } else {
                        $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Invalid format value: %1!", $invalidColumnFormat) . '"');
                    }

                    $parameters[] = [
                        'message' => count($invalidFormat) > 1 ? __("Invalid format values: %1!", $invalidColumnFormat) : __("Invalid format value: %1!", $invalidColumnFormat),
                        'row' => $key + 1,
                        'columns' => $invalidFormat
                    ];

                    $message['rows_errors']++;
                    $message['rows_not_changed']++;
                    $message['rows_processed']++;

                    continue;
                }

                /* validate data sku */
                $invalidSku[] = 'sku';
                if (str_contains($productSku, '-')) {
                    $sku = explode("-", $productSku);
                    $product = $this->productFactory->create()->getCollection()
                        ->addFieldToFilter('sku', ["eq"=>$productSku])
                        ->getFirstItem();
                    if ($vendorCode && $sku[0] === $vendorCode && !empty($product->getData())) {
                        $invalidSku = [];
                    }
                }

                if (!empty($invalidSku)) {
                    $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Invalid SKU!") . '"');
                    $parameters[] = [
                        'message' => __("Invalid SKU!"),
                        'row' => $key + 1,
                        'columns' => $invalidSku
                    ];

                    $message['rows_errors']++;
                    $message['rows_not_changed']++;
                    $message['rows_processed']++;

                    continue;
                }

                /* validate data customer group */
                $invalidCustomerGroup = [];
                $customerGroupId = $customerGroup;
                if ($customerGroup !== "*") {
                    $customerGroupId = $this->tierPriceHelper->getDataCustomerGroupId($customerGroup);
                    if (isset($customerGroupId['status']) && $customerGroupId['status'] == false) {
                        $invalidCustomerGroup[] = 'customer_group';
                    } elseif (is_array($customerGroupId)) {
                        $invalidCustomerGroup[] = 'customer_group';
                    }
                }

                if (!empty($invalidCustomerGroup)) {
                    $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Invalid customer group!") . '"');

                    $parameters[] = [
                        'message' => __("Invalid customer group!"),
                        'row' => $key + 1,
                        'columns' => $invalidCustomerGroup
                    ];

                    $message['rows_errors']++;
                    $message['rows_not_changed']++;
                    $message['rows_processed']++;

                    continue;
                }

                /* validate data start date */
                $invalidStartDate = [];
                $startDate = $this->timezoneInterface->date(strtotime($startDate))->format('Y-m-d');
                if (!empty($startDate)) {
                    $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
                    if ($startDate < $currentDate) {
                        $invalidStartDate[] = 'start_date';
                    }
                }

                if (!empty($invalidStartDate)) {
                    $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Start date can't be earlier than or equal to current date!") . '"');

                    $parameters[] = [
                        'message' => __("Start date can't be earlier than or equal to current date!"),
                        'row' => $key + 1,
                        'columns' => $invalidStartDate
                    ];

                    $message['rows_errors']++;
                    $message['rows_not_changed']++;
                    $message['rows_processed']++;

                    continue;
                }

                /* validate data end date */
                $invalidEndDate = [];
                $endDate = $this->timezoneInterface->date(strtotime($endDate))->format('Y-m-d');

                if (!empty($startDate) && !empty($endDate)) {
                    $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
                    if ($endDate <= $startDate) {
                        $invalidEndDate[] = 'end_date';
                    }
                }

                if (empty($startDate) && !empty($endDate)) {
                    if ($endDate <= $currentDate) {
                        $invalidEndDate[] = 'end_date';
                    }
                }

                if (!empty($invalidEndDate)) {
                    $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("End date can't be earlier than or equal to start date or current date!") . '"');

                    $parameters[] = [
                        'message' => __("End date can't be earlier than or equal to start date or current date!"),
                        'row' => $key+1,
                        'columns' => $invalidEndDate
                    ];

                    $message['rows_errors']++;
                    $message['rows_not_changed']++;
                    $message['rows_processed']++;

                    continue;
                }

                /* validate data value discount_percentage and discount_amount */
                $invalidDiscountPercentage = [];
                $discountPercentage = null;
                $discountAmount = null;
                if (strtolower($discountType) == self::CUSTOMER_TYPE_PERCENTAGE) {
                    if ((int)$discountValue > 100) {
                        $invalidDiscountPercentage[] = 'discount_value';

                        $this->errorLog->info('Error,' . ($key + 1) . ',"' . __("Discount percentage value must be between 0-100!") . '"');

                        $parameters[] = [
                            'message' => __("Discount percentage value must be between 0-100!"),
                            'row' => $key + 1,
                            'columns' => $invalidDiscountPercentage
                        ];

                        $message['rows_errors']++;
                        $message['rows_not_changed']++;
                        $message['rows_processed']++;

                        continue;
                    } else {
                        $discountPercentage = $discountValue;
                    }
                } else { // fixed
                    $discountAmount = $discountValue;
                }

                /* validate customer email */
                if ((strtolower($customerType) == self::CUSTOMER_TYPE_EMAIL) && isset($customerValue) && $customerValue !== '*') {
                    $customer = $this->customerFactory->create()->getCollection()->addAttributeToSelect("*")
                                ->addFieldToFilter('email', ["eq" => $customerValue])
                                ->getFirstItem();

                    if (!$customer->getId()) {
                        $invalidEmail[] = 'customer_value';
                        $parameters[] = [
                            'message' => __("Email not found !"),
                            'row' => $key+1,
                            'columns' => $invalidEmail
                        ];
                        continue;
                    }
                }

                /* mapping data */
                $erpId = null;
                $customerId = $customerValue;
                if ($customerValue !== "*") {
                    $customer = $this->customerFactory->create()->getCollection()
                        ->addAttributeToSelect("*")
                        ->addFieldToFilter('email', ["eq"=>$customerValue])
                        ->getFirstItem();
                    $customerId = $customer->getId() ?? null;
                }

                $mapData[] = [
                    'erp_promo_id' => $erpPromoId,
                    'erp_id' => $erpId,
                    'vendor_code' => $vendorCode,
                    'customer_group_id' => $customerGroupId,
                    'customer_id' => $customerId,
                    'product_sku' => $productSku,
                    'step_qty' => $stepQty,
                    'discount_percentage' => $discountPercentage,
                    'discount_amount' => $discountAmount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'creator' => $creator,
                    'customerId' => $customerId,
                    'apply_to_price' => $applyToPrice
                ];
            }
        }

        if (!empty($message)) {
            $mapData['failed'] = [
                'parameters' => $parameters,
                'message' => $message
            ];
        }

        return $mapData;
    }

    /* check required values
     * customer_type
     * customer_value
     * customer_group
     * sku
     * step_qty
     */
    public function checkRequiredValues($customerType, $customerValue, $customerGroup, $productSku, $stepQty, $discountType, $discountValue)
    {
        $valuesEmpty = [];
        if (empty($customerType)) {
            $valuesEmpty[] = 'customer_type';
        }
        if (empty($customerValue)) {
            $valuesEmpty[] = 'customer_value';
        }
        if (empty($customerGroup)) {
            $valuesEmpty[] = 'customer_group';
        }
        if (empty($productSku)) {
            $valuesEmpty[] = 'sku';
        }
        if (empty($stepQty)) {
            $valuesEmpty[] = 'step_qty';
        }
        if (empty($discountType)) {
            $valuesEmpty[] = 'discount_type';
        }
        if (empty($discountValue)) {
            $valuesEmpty[] = 'discount_value';
        }

        return $valuesEmpty;
    }

    /* check format values
     * customer_type(erp or email)
     * step_qty(int)
     * discount_type(percentage or fixed)
     * discount_value(decimal)
     * start_date(timestamp)
     * end_date(timestamp)
    */
    public function checkFormatValues($customerType, $stepQty, $discountType, $discountValue, $startDate, $endDate, $applyToPrice)
    {
        $invalidFormat = [];
        if ((strtolower($customerType) != self::CUSTOMER_TYPE_ERP) && (strtolower($customerType) != 'email')) {
            $invalidFormat[] = 'customer_type';
        }
        if (!preg_match("/^[0-9]+$/i", $stepQty)) {
            $invalidFormat[] = 'step_qty';
        }
        if ((strtolower($discountType) !== self::CUSTOMER_TYPE_PERCENTAGE) && (strtolower($discountType) !== 'fixed')) {
            $invalidFormat[] = 'discount_type';
        }
        if (!is_numeric($discountValue)) {
            $invalidFormat[] = 'discount_value';
        }
        if (!strtotime($startDate) && !empty($startDate)) {
            $invalidFormat[] = 'start_date';
        }
        if (!strtotime($endDate) && !empty($endDate)) {
            $invalidFormat[] = 'end_date';
        }
        if (!is_numeric($applyToPrice) || $applyToPrice < 0 || $applyToPrice > 1) {
            $invalidFormat[] = 'apply_to_price';
        }

        return $invalidFormat;
    }
}
