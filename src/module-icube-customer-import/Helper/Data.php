<?php
namespace Icube\CustomerImport\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $csv;
    protected $file;
    protected $resourceConnection;
    protected $dir;
    protected $indexFactory;

    public function __construct(
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Indexer\Model\IndexerFactory $indexFactory
    ) {
        $this->csv = $csv;
        $this->file = $file;
        $this->resourceConnection = $resourceConnection;
        $this->dir = $dir;
        $this->indexFactory = $indexFactory;
    }

    public function runImport($limit = null, $startRow = null) 
    {
        $inputedCount = 0;
        $lastRow = 0;

        $csvFile = $this->dir->getPath('var').'/import/Magento_x_AWP_Data_Customer.csv';
        try {
            if ($this->file->isExists($csvFile)) {
                $this->csv->setDelimiter(",");
                $csvData = $this->csv->getData($csvFile);
                if ($csvData) {
                    $connection = $this->resourceConnection->getConnection();

                    // Get table name
                    $eavTable = $connection->getTableName('eav_attribute');
                    $eavAttributeOptionTable = $connection->getTableName('eav_attribute_option');
                    $eavAttributeOptionValueTable = $connection->getTableName('eav_attribute_option_value');

                    $customerEntityTable = $connection->getTableName('customer_entity');
                    $customerEntityVarcharTable = $connection->getTableName('customer_entity_varchar');
                    $customerEntityTextTable = $connection->getTableName('customer_entity_text');

                    $customerAddressEntityTable = $connection->getTableName('customer_address_entity');
                    $customerAddressEntityTextTable = $connection->getTableName('customer_address_entity_text');
                    $customerAddressEntityVarcharTable = $connection->getTableName('customer_address_entity_varchar');
                    $customerAddressEntityIntTable = $connection->getTableName('customer_address_entity_int');
                    
                    $directoryCountryRegionTable = $connection->getTableName('directory_country_region');

                    // Get customer attribute list
                    $query = "SELECT backend_type,attribute_id,attribute_code FROM ".$eavTable." WHERE entity_type_id=1 AND attribute_code IN ('warung_name','wp_code','referral_code','referral_name','ktp_number','status_jds','status_note','verification_status') ";
                    $customerAttributeResult = $connection->fetchAll($query);

                    // Get customer address attribute list
                    $query = "SELECT backend_type,attribute_id,attribute_code FROM ".$eavTable." WHERE entity_type_id=2 AND attribute_code IN ('city', 'longitude', 'latitude', 'road_access', 'is_alley', 'country_id', 'postcode', 'region', 'street', 'district', 'village') ";
                    $customerAddressAttributeResult = $connection->fetchAll($query);

                    // Get region list
                    $query = "SELECT default_name,region_id FROM ".$directoryCountryRegionTable." WHERE country_id='ID'";
                    $result = $connection->fetchAll($query);
                    foreach($result as $data) {
                        $upperRegionName = strtoupper($data['default_name']);
                        $regionListResult[$upperRegionName] = $data;
                    }

                    // Mapping gender
                    $genderMapping = [
                        "Male" => 1,
                        "Female" => 2 
                    ];

                    // Mapping customer eav attribute table
                    $customerEavAttributeEntityTableName = [
                        "varchar" => $customerEntityVarcharTable,
                        "text" => $customerEntityTextTable
                    ];

                    // Mapping customer address eav attribute table
                    $customerAddressEavAttributeEntityTableName = [
                        "varchar" => $customerAddressEntityVarcharTable,
                        "text" => $customerAddressEntityTextTable,
                        "static" => $customerAddressEntityVarcharTable,
                        "int" => $customerAddressEntityIntTable,
                    ];

                    // get verification status
                    $verificationStatusMapping = [];

                    $query = "SELECT eaov.option_id, eaov.value FROM ".$eavTable." ea JOIN ".$eavAttributeOptionTable." eao ON eao.attribute_id=ea.attribute_id 
                    JOIN ".$eavAttributeOptionValueTable." eaov ON eaov.option_id=eao.option_id WHERE ea.attribute_code='verification_status' AND eaov.store_id=0";
                    $result = $connection->fetchAll($query);
                    foreach($result as $data) {
                        $verificationStatusMapping[$data['value']] = $data['option_id'];
                    }
                    
                    foreach ($csvData as $row => $data) {
                        if ($startRow && $row < $startRow) {
                            continue;
                        }

                        if ($limit) {
                            if ($startRow) {
                                $limitRowNumber = ($startRow+$limit)-1;
                            } else {
                                $limitRowNumber = $limit;
                            }

                            if ($row > $limitRowNumber) {
                                $lastRow = $row;
                                break;
                            }
                        }

                        if ($row > 0) {
                            if (isset($data[0]) && $data[0] && $data[0] != null) 
                            {
                                // Mapping gender
                                $genderId = isset($genderMapping[$data[6]]) ? $genderMapping[$data[6]] : "NULL";

                                // Mapping customer data
                                $email = $data[0] ? $connection->quote($data[0]) : "NULL";
                                $created_at = $data[1] ? $connection->quote(date('Y-m-d H:i:s', strtotime($data[1]))) : "NULL";
                                $firstname = $data[2] ? $connection->quote($data[2]) : "NULL";
                                $lastname = $data[3] ? $connection->quote($data[3]) : "NULL";
                                $dob = $data[4] ? $connection->quote(date('Y-m-d', strtotime($data[4]))) : null;
                                $telephone = $data[9] ? $connection->quote($data[9]) : "NULL";
                                $whatsapp_number = $data[10] ? $connection->quote($data[10]) : "NULL";
                                $business_type = $data[24] ? $connection->quote($data[24]) : "NULL";
                                $group_id = $data[29] ? $connection->quote($data[29]) : 1;

                                // Input customer entity
                                $query = "INSERT INTO $customerEntityTable (email, created_at, firstname, lastname, dob, gender, telephone, whatsapp_number, business_type, website_id, group_id, store_id) VALUES ($email,$created_at,$firstname,$lastname,".($dob ? $dob : "NULL").",$genderId,$telephone,$whatsapp_number,$business_type,1,$group_id, 1) AS customerEntity ON DUPLICATE KEY UPDATE email=customerEntity.email,created_at=customerEntity.created_at,firstname=customerEntity.firstname,lastname=customerEntity.lastname,dob=customerEntity.dob,gender=customerEntity.gender,telephone=customerEntity.telephone,whatsapp_number=customerEntity.whatsapp_number,business_type=customerEntity.business_type,website_id=customerEntity.website_id,group_id=customerEntity.group_id,store_id=customerEntity.store_id,entity_id=LAST_INSERT_ID(entity_id)";
                                
                                $connection->query($query);

                                // Get customer id
                                $customerId = $connection->lastInsertId();

                                // Mapping verification status
                                $verificationStatusId = isset($verificationStatusMapping[$data[12]]) ? $verificationStatusMapping[$data[12]] : "NULL";

                                // Mapping eav data
                                $eavData = [
                                    'wp_code' => $data[5] ? $connection->quote($data[5]) : null,
                                    'referral_code' => $data[7] ? $connection->quote($data[7]) : null,
                                    'referral_name' => $data[8] ? $connection->quote($data[8]) : null,
                                    'ktp_number' => $data[11] ? $connection->quote($data[11]) : null,
                                    'status_jds' => $data[13] ? $connection->quote($data[13]) : null,
                                    'status_note' => $data[14] ? $connection->quote($data[14]) : null,
                                    'warung_name' => $data[25] ? $connection->quote($data[25]) : null,
                                    'verification_status' => $verificationStatusId
                                ];

                                // Input Customer Entity Attribute
                                if ($customerAttributeResult) {
                                    foreach($customerAttributeResult as $attributeData) 
                                    {
                                        $attributeId = $attributeData['attribute_id'];
                                        $attributeCode = $attributeData['attribute_code'];

                                        if (isset($eavData[$attributeCode]) && $eavData[$attributeCode] && $eavData[$attributeCode] != "NULL") 
                                        {
                                            $tableName = isset($customerEavAttributeEntityTableName[$attributeData['backend_type']]) ? $customerEavAttributeEntityTableName[$attributeData['backend_type']] : null;
                                            if ($tableName) {
                                                $query = "INSERT INTO $tableName (attribute_id, entity_id, value) VALUES ($attributeId, $customerId, $eavData[$attributeCode]) AS customerEntity ON DUPLICATE KEY UPDATE value=customerEntity.value";
                                                $connection->query($query);
                                            }
                                        }
                                    }
                                }

                                $regionName = $data[22] ? $data[22] : null;

                                // Mapping customer address data
                                $addressEavData = [
                                    'city' => $data[15] ? $connection->quote($data[15]) : "NULL",
                                    'longitude' => $data[16] ? $connection->quote($data[16]) : "NULL",
                                    'latitude' => $data[17] ? $connection->quote($data[17]) : "NULL",
                                    'road_access' => $data[18] ? $connection->quote($data[18]) : "NULL",
                                    'is_alley' => $data[19] ? $connection->quote($data[19]) : "NULL",
                                    'country_id' => $data[20] ? $connection->quote($data[20]) : "NULL",
                                    'postcode' => $data[21] ? $connection->quote($data[21]) : "NULL",
                                    'street' => $data[23] ? $connection->quote($data[23]) : "NULL",
                                    'district' => $data[26] ? $connection->quote($data[26]) : "NULL",
                                    'village' => $data[27] ? $connection->quote($data[27]) : "NULL"
                                ];

                                // Get region id
                                $regionId = null;
                                if ($regionName && $regionListResult) {
                                    $upperRegionName = strtoupper($regionName);
                                    if (isset($regionListResult[$upperRegionName]) && $regionListResult[$upperRegionName]) {
                                        $regionData = $regionListResult[$upperRegionName];

                                        $regionName = $regionData['default_name'];
                                        $regionId = $regionData['region_id'];
                                    }
                                }

                                $addressEavData['region'] = $regionName ? $connection->quote($regionName) : "NULL";

                                // Input Customer Address Entity
                                $query = "INSERT INTO $customerAddressEntityTable (parent_id, created_at, is_active, city, country_id, firstname, lastname, postcode, region, street, telephone, company, region_id) VALUES ($customerId,$created_at,1,".$addressEavData['city'].", ".$addressEavData['country_id'].", ".($firstname && $firstname != "NULL" ? $firstname : "''").", ".($lastname && $lastname != "NULL" ? $lastname : "''").", ".$addressEavData['postcode'].", ".$addressEavData['region'].",".$addressEavData['street'].", ".($telephone && $telephone != "NULL" ? $telephone : "''").", ".$eavData['warung_name'].", ".($regionId ? $regionId : "''").") AS customerEntity ON DUPLICATE KEY UPDATE parent_id=customerEntity.parent_id,created_at=customerEntity.created_at,is_active=customerEntity.is_active,city=customerEntity.city,country_id=customerEntity.country_id,firstname=customerEntity.firstname,lastname=customerEntity.lastname,postcode=customerEntity.postcode,region=customerEntity.region,street=customerEntity.street,telephone=customerEntity.telephone,company=customerEntity.company,region_id=customerEntity.region_id,entity_id=LAST_INSERT_ID(entity_id)";
                                $connection->query($query);

                                // Get customer address id
                                $customerAddressId = $connection->lastInsertId();

                                // Update customer entity
                                $query = "UPDATE $customerEntityTable SET default_billing=$customerAddressId, default_shipping=$customerAddressId WHERE entity_id=$customerId";
                                $connection->query($query);

                                // Input customer address attribute
                                if ($customerAddressAttributeResult) {
                                    foreach($customerAddressAttributeResult as $attributeData) 
                                    {
                                        $attributeId = $attributeData['attribute_id'];
                                        $attributeCode = $attributeData['attribute_code'];

                                        if (isset($addressEavData[$attributeCode]) && $addressEavData[$attributeCode] && $addressEavData[$attributeCode] != "NULL") 
                                        {
                                            $tableName = isset($customerAddressEavAttributeEntityTableName[$attributeData['backend_type']]) ? $customerAddressEavAttributeEntityTableName[$attributeData['backend_type']] : null;
                                            if ($tableName) {
                                                $query = "INSERT INTO $tableName (attribute_id, entity_id, value) VALUES ($attributeId, $customerAddressId, $addressEavData[$attributeCode]) AS customerEntity ON DUPLICATE KEY UPDATE value=customerEntity.value";
                                                $connection->query($query);
                                            }
                                        }
                                    }
                                }

                                $inputedCount++;
                            }
                        }
                    }
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Csv file does not exist'));
            }
        } catch (FileSystemException $e) {
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
        }

        if ($inputedCount) {
            $index = 'customer_grid';

            $indexFactory = $this->indexFactory->create()->load($index);
            $indexFactory->reindexAll($index);
        }

        if (!$startRow) {
            $startRow = 1;
        }

        return __('%1 customer inputed/updated successfully. Executed Row %2-%3', $inputedCount, $startRow, $lastRow);
    }
}