<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-04T14:37:28+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Spreadsheet.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor;

use Magento\Framework\Exception\LocalizedException;

class Spreadsheet extends AbstractProcessor
{
    protected $config = [];
    protected $headerRow;
    protected $rowData;

    protected function initConfiguration()
    {
        if (!$this->config) {
            # Load configuration:
            $this->config = [
                'IMPORT_SKIP_HEADER' => $this->getConfigValue('skip_header')
            ];

            if (!class_exists('\PhpOffice\PhpSpreadsheet\Reader\Xls')) {
                throw new LocalizedException(
                    __(
                        'The phpoffice/phpspreadsheet library is not installed. No spreadsheets can be parsed. Please install the library as explained in our wiki using composer in order to use this import processor.'
                    )
                );
            }

            # Get mapping model
            $this->mappingModel = $this->mappingFieldsFactory->create();
            $this->mappingModel->setMappingData($this->getConfigValue('mapping'));

            # Load mapping
            $this->mapping = $this->mappingModel->getMapping();
        }
    }

    public function getRowsToProcess($filesToProcess)
    {
        try {
            ini_set('auto_detect_line_endings', 1);
        } catch (\Exception $e) {}

        # Updates to process, later the result
        $updatesInFilesToProcess = [];

        $this->initConfiguration();

        foreach ($filesToProcess as $importFile) {
            $data = $importFile['data'];
            $filename = $importFile['filename'];
            unset($importFile['data']);

            $updatesToProcess = [];
            $foundFields = [];
            $rowCounter = 0;

            $fileExtension = pathinfo($filename)['extension'];
            $tmpFile = tempnam(sys_get_temp_dir(), 'spreadsheet') . $fileExtension;
            file_put_contents($tmpFile, $data);
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($tmpFile);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);
            try {
                $spreadsheet = $reader->load($tmpFile);
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                unlink($tmpFile);
                throw new \Exception('Error parsing spreadsheet file: ' . $e->getMessage());
            }

            $worksheet = $spreadsheet->getActiveSheet();
            $this->headerRow = [];
            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $this->rowData = $rowData;

                $rowCounter++;
                if ($rowCounter == 1) {
                    // Skip the header line but parse it for field names.
                    $numberOfFields = count($rowData);
                    for ($i = 0; $i < $numberOfFields; $i++) {
                        $this->headerRow[$rowData[$i]] = $i;
                    }
                    if ($this->config['IMPORT_SKIP_HEADER'] == true) {
                        continue;
                    }
                }

                $skipRow = false;
                // First run: Get order number for row
                $rowIdentifier = "";
                foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                    if ($fieldData['field'] == 'order|increment_id' || $fieldData['field'] == 'customer|id') {
                        $fieldValue = $this->getFieldData($fieldData);
                        if (!empty($fieldValue)) {
                            $rowIdentifier = $fieldValue;
                        }
                    }
                    // Check if row should be skipped.
                    if (true === $this->fieldsConfiguration->checkSkipImport(
                            $fieldData['field'],
                            $fieldData['config'],
                            $this
                        )
                    ) {
                        $skipRow = true;
                    }
                }
                if (empty($rowIdentifier) && !$skipRow) {
                    // Alternatively look for ext_order_id and try to group by that;
                    foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                        if ($fieldData['field'] == 'order|ext_order_id' || $fieldData['field'] == 'customer|email') {
                            $fieldValue = $this->getFieldData($fieldData);
                            if (!empty($fieldValue)) {
                                $rowIdentifier = $fieldValue;
                            }
                            break;
                        }
                    }
                    if (empty($rowIdentifier)) {
                        $rowIdentifier = $rowCounter;
                    }
                }
                if ($skipRow) {
                    $rowIdentifier .= '_SKIP';
                }
                if (!isset($updatesToProcess[$rowIdentifier])) {
                    $updatesToProcess[$rowIdentifier] = [];
                    $rowArray = [];
                } else {
                    $rowArray = $updatesToProcess[$rowIdentifier];
                }

                foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                    if (isset($fieldData['disabled']) && $fieldData['disabled']) {
                        continue;
                    }
                    $fieldName = $fieldData['field'];
                    $fieldValue = $this->getFieldData($fieldData);
                    if ($fieldValue !== '') {
                        if (!in_array($fieldName, $foundFields)) {
                            $foundFields[] = $fieldName;
                        }

                        if (isset($fieldData['group']) && !empty($fieldData['group'])) {
                            if (strstr($fieldName, '|')) {
                                $splitFieldName = explode("|", $fieldName);
                                $fieldName = array_pop($splitFieldName);
                            }
                            $rowArray[$fieldData['group']][$rowCounter - 1][$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                        } else {
                            if (strstr($fieldName, '|')) {
                                $splitFieldName = explode("|", $fieldName);
                                $rowArray[$splitFieldName[0]][$splitFieldName[1]] = $this->mappingModel->formatField($fieldName, $fieldValue);
                            } else {
                                $rowArray[$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                            }
                        }
                    }
                }
                if ($skipRow) {
                    // Field in field_configuration XML determined that this row should be skipped. "<skip>" parameter in XML field config
                    $rowArray['SKIP_FLAG'] = true;
                }
                $updatesToProcess[$rowIdentifier] = $rowArray;
            }

            // Output the header row in a nicer string
            $hasHeaderRow = ($this->config['IMPORT_SKIP_HEADER']) ? "Yes" : "No";
            $headerRowTemp = $this->headerRow ? $this->headerRow : [];
            array_walk($headerRowTemp, function(&$i, $k) {
                $i = " \"$k\"=\"$i\"";
            });
            // File processed
            $updatesInFilesToProcess[] = [
                "FILE_INFORMATION" => $importFile,
                "HEADER_ROW" => "Skip header row: " . $hasHeaderRow . " | Header row:" . implode("", $headerRowTemp),
                "FIELDS" => $foundFields,
                "ROWS" => $updatesToProcess
            ];
        }

        try {
            ini_set('auto_detect_line_endings', 0);
        } catch (\Exception $e) {}

        #ini_set('xdebug.var_display_max_depth', 10);
        #Zend_Debug::dump($updatesToProcess);
        #die();

        return $updatesInFilesToProcess;
    }

    public function getFieldPos($mappedField)
    {
        if (!is_numeric($mappedField) && isset($this->headerRow[$mappedField])) {
            return $this->headerRow[$mappedField];
        } else {
            return $mappedField;
        }
    }

    /**
     * @param $fieldData
     *
     * @return mixed
     * Wrapper function to manipulate field data returned
     */
    public function getFieldData($fieldData)
    {
        $returnData = $this->getFieldDataRaw($fieldData);
        $returnData = $this->fieldsConfiguration->manipulateFieldFetched(
            $fieldData['field'],
            $returnData,
            $fieldData['config'],
            $this
        );
        return $returnData;
    }

    public function getFieldDataRaw($fieldData, $bypassFieldConfiguration = false)
    {
        $field = $fieldData['field'];
        $fieldPos = $this->getFieldPos($fieldData['value']);
        if (isset($this->rowData[$fieldPos])) {
            $data = $this->rowData[$fieldPos];
            if (!$bypassFieldConfiguration) {
                $data = $this->fieldsConfiguration->handleField($field, $data, $fieldData['config']);
            }
            if (($data === '' || $data === null || $data === false) && isset($fieldData['id'])) {
                // Try to get the default value at least.. otherwise ''
                $data = $this->mappingModel->getDefaultValue($fieldData['id']);
            }
        } else {
            if (!$bypassFieldConfiguration) {
                $data = $this->fieldsConfiguration->handleField($field, '', $fieldData['config']);
            } else {
                $data = '';
            }
            if (empty($data) && isset($fieldData['id'])) {
                // Try to get the default value at least.. otherwise ''
                $data = $this->mappingModel->getDefaultValue($fieldData['id']);
            }
        }
        return trim($data);
    }
}
