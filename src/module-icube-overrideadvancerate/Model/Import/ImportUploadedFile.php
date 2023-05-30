<?php

namespace Icube\OverrideAdvancerate\Model\Import;

use Ced\Advancerate\Model\ResourceModel\Carrier\Advancerate;


class ImportUploadedFile extends Advancerate
{

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ced\Advancerate\Model\Carrier\Advancerate $carrierTablerate,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\Session $citySession,
        $connectionName = null
    ) {
        parent::__construct($context, $logger, $coreConfig, $storeManager, $carrierTablerate, $countryCollectionFactory, $regionCollectionFactory, $filesystem, $citySession, $connectionName);
    }  

    public function executeImport($fileName, $path, $directoryCode)
    {
        $csvFile = $fileName;

        $this->_importErrors = [];
        $this->_importedRows = 0;
        
        $directory = $this->_filesystem->getDirectoryRead($directoryCode);
        $filePath = $directory->getRelativePath($path.$csvFile);
        $stream = $directory->openFile($filePath);
        
        // // check and skip headers
        $headers = $stream->readCsv();
        
        if ($headers === false || count($headers) < 15) {
            $stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct Advance Rates File Format.'));
        }

        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $rowNumber = 1;
            $importData = [];

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            while (false !== ($aRowData = $stream->readCsv())) {
                $rowNumber++;

                if (empty($aRowData)) {
                    continue;
                }

                $row = $this->_getImportRow($aRowData, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }

                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = [];
                }
            }
            $this->_saveImportData($importData);
            $stream->close();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $connection->rollback();
            $stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $connection->rollback();
            $stream->close();
            $this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing advance rates.')
            );
        }

        $connection->commit();

        if ($this->_importErrors) {
            $error = __(
                'We couldn\'t import this file because of these errors: %1',
                implode(" \n", $this->_importErrors)
            );
            throw new \Magento\Framework\Exception\LocalizedException($error);
        }

        return $this;
    }
    
     /**
     * Validate row for import and return table rate array or false
     * Error will be add to _importErrors array
     *
     * @param array $row
     * @param int $rowNumber
     * @return array|false
     */
    protected function _getImportRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 15) {
            $this->_importErrors[] = __('Please correct Table Rates format in the Row #%1.', $rowNumber);
            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = $v;
        }

        $WebsiteId = 1;

        // validate country
        if (isset($this->_importIso2Countries[$row[0]])) {
            $countryId = $this->_importIso2Countries[$row[0]];
        } elseif (isset($this->_importIso3Countries[$row[0]])) {
            $countryId = $this->_importIso3Countries[$row[0]];
        } elseif ($row[0] == '*' || $row[0] == '') {
            $countryId = '0';
        } else {
            $this->_importErrors[] = __('Please correct Country "%1" in the Row #%2.', $row[0], $rowNumber);
            return false;
        }
        // validate region
        if ($countryId != '0' && isset($this->_importRegions[$countryId][$row[1]])) {
            $regionId = $this->_importRegions[$countryId][$row[1]];
        } elseif ($row[1] == '*' || $row[1] == '') {
            $regionId = 0;
        } else {
            $this->_importErrors[] = __('Please correct Region/State "%1" in the Row #%2.', $row[1], $rowNumber);
            return false;
        }
        // detect city
        if ($row[2] == '*' || $row[2] == '') {
            $city = '*';
        } else {
            $city = $row[2];
        }
        
        // detect zip code
        if ($row[3] == '*' || $row[3] == '') {
            $zipCode = '*';
        } else {
            $zipCode = $row[3];
        }
        
        // detect weight From
        
        if ($row[4] == '*' || $row[4] == '') {
            $weight_from = '0.0000';
        } else {
            $weight_from = $this->_parseDecimalValue($row[4]);
            if ($weight_from === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Weight From', $row[4], $rowNumber
                );
                return false;
            }
        }
        
        // detect weight to
        if ($row[5] == '*' || $row[5] == '') {
            $weight_to = '0.0000';
        } else {
            $weight_to = $this->_parseDecimalValue($row[5]);
            if ($weight_to === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Weight To', $row[5], $rowNumber
                );
                return false;
            }
        }
        
        // detect price from
        if ($row[6] == '*' || $row[6] == '') {
            $price_from = '0.0000';
        } else {
            $price_from = $this->_parseDecimalValue($row[6]);
            if ($price_from === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Price From', $row[6], $rowNumber
                );
                return false;
            }
        }
        
        // detect price to
        if ($row[7] == '*' || $row[7] == '') {
            $price_to = '0.0000';
        } else {
            $price_to = $this->_parseDecimalValue($row[7]);
            if ($price_to === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Price To', $row[7], $rowNumber
                );
                return false;
            }
        }
        
        // detect Qty from
        if ($row[8] == '*' || $row[8] == '') {
            $qty_from = '0';
        } else {
            $qty_from = $row[8];
        }
        
        // detect Qty to
        if ($row[9] == '*' || $row[9] == '') {
            $qty_to = '0';
        } else {
            $qty_to = $row[9];
        }
        
        // validate Shipping price
        $shipping_price = $this->_parseDecimalValue($row[10]);
        if ($shipping_price === false) {
            $this->_importErrors[] = __('Please correct Shipping Price "%1" in the Row #%2.', $row[10], $rowNumber);
            return false;
        }
        
        $shipping_method = preg_replace(array("/[^a-z0-9_]/","/\_+/"), '_', strtolower($row[11]));
        if ($shipping_method == '' || $shipping_method == '_') {
            $this->_importErrors[] = ___('Invalid Shipping Method Name "%s" in the Row #%s.', $row[11], $rowNumber);
            return false;
        }
        $shipping_label = $row[11];
        $etd = $row[12];
        $vendorId = $row[13];
        $wilayah = ($row[14] == "") ? NULL : $row[14];
        $customer_group = $row[15];
        return [
            $WebsiteId,$vendorId, $countryId, $regionId, $city, $zipCode,                   
            $weight_from, $weight_to, $price_from, $price_to, $qty_from,
            $qty_to, $shipping_price, $shipping_method,$shipping_label, $etd, $wilayah, $customer_group          
        ];
    }
    
    /**
     * Save import data batch
     * @param array $data
     * @return \Ced\Advancerate\Model\Resource\Carrier\Advancerate
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = [
                'website_id','vendor_id','dest_country_id','dest_region_id','city','dest_zip',
                'weight_from','weight_to','price_from','price_to','qty_from',
                'qty_to','price','shipping_method','shipping_label', 'etd', 'wilayah', 'customer_group'
            ];
            $this->getConnection()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }
          
        return $this;
    }
}
