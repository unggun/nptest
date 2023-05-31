<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-04T14:15:52+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Xml.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor;

use Magento\Framework\Exception\LocalizedException;

class Xml extends AbstractProcessor
{
    #protected $update = null;
    protected $rowData;

    public function getRowsToProcess($filesToProcess)
    {
        # Get some more detailed error information from libxml
        libxml_use_internal_errors(true);

        $logger = $this->xtentoLogger;

        # Updates to process, later the result
        $updatesInFilesToProcess = [];

        # Get mapping model
        $this->mappingModel = $this->mappingFieldsFactory->create();
        $this->mappingModel->setMappingData($this->getConfigValue('mapping'));

        # Load mapping
        $this->mapping = $this->mappingModel->getMappingConfig();

        # Load configuration:
        $config = [
            'IMPORT_DATA_XPATH' => $this->getConfigValue('xpath_data'),
        ];

        if ($config['IMPORT_DATA_XPATH'] == '') {
            throw new LocalizedException(
                __(
                    'Please configure the XML Processor in the configuration section of this import profile. The Data XPath field may not be empty.'
                )
            );
        }

        $importDataXpath = explode("!|!", $config['IMPORT_DATA_XPATH']);
        $config['IMPORT_DATA_XPATH'] = $importDataXpath[0];
        $replaceStrings = [];
        if (isset($importDataXpath[1])) {
            // "Replace" value in import data XPath: //orders/order!|!ns1:,ns2: - will lead to the strings "ns1:" and "ns2" being removed from the file
            $replaceStrings = explode(",", $importDataXpath[1]);
        }

        foreach ($filesToProcess as $importFile) {
            $data = $importFile['data'];
            $filename = $importFile['filename'];
            unset($importFile['data']);

            // Remove UTF8 BOM
            $bom = pack('H*', 'EFBBBF');
            $data = preg_replace("/^$bom/", '', $data);

            $updatesToProcess = [];
            $foundFields = [];

            // Prepare data - replace namespace
            $data = str_replace(
                'xmlns=',
                'ns=',
                $data
            ); // http://www.php.net/manual/en/simplexmlelement.xpath.php#96153
            $data = str_replace(
                'xmlns:',
                'ns:',
                $data
            ); // http://www.php.net/manual/en/simplexmlelement.xpath.php#96153

            // Replace values from "data xpath" field
            if (!empty($replaceStrings)) {
                $data = str_replace($replaceStrings, '', $data);
            }

            #$loadEntities = libxml_disable_entity_loader(true);
            try {
                $xmlDOM = new \DOMDocument();
                $libxmlConstants = null;
                if (defined('LIBXML_PARSEHUGE')) {
                    $libxmlConstants = LIBXML_PARSEHUGE;
                }
                $xmlDOM->loadXML($data, $libxmlConstants);
            } catch (\Exception $e) {
                $errors = "Could not load XML File '" . $filename . "':\n" . $e->getMessage();
                foreach (libxml_get_errors() as $error) {
                    $errors .= "\t" . $error->message;
                }
                $logger->info($errors);
                #libxml_disable_entity_loader($loadEntities);
                continue; # Process next file..
            }

            if (!$xmlDOM) {
                $errors = "Could not load XML File '" . $filename . "'.";
                foreach (libxml_get_errors() as $error) {
                    $errors .= "\t" . $error->message;
                }
                $logger->info($errors);
                #libxml_disable_entity_loader($loadEntities);
                continue; # Process next file..
            }

            $updateCounter = 0;
            $domXPath = new \DOMXPath($xmlDOM);
            $updates = $domXPath->query($config['IMPORT_DATA_XPATH']);
            if (empty($updates)) {
                continue; // No updates found, invalid XPath?
            }
            foreach ($updates as $update) {
                $updateCounter++;
                // Init "sub dom"
                $updateDOM = new \DomDocument;
                $updateDOM->appendChild($updateDOM->importNode($update, true));
                $updateXPath = new \DOMXPath($updateDOM);
                #$this->update = $updateXPath;
                $this->rowData = $updateXPath;

                #var_dump($updateDOM->saveXML()); die();

                $skipRow = false;
                // First run: Get order number for row
                $rowIdentifier = "";
                foreach ($this->mappingModel->getMapping() as $fieldId => $fieldData) {
                    if ($fieldData['field'] == 'order|increment_id' || $fieldData['field'] == 'customer|id') { //X or "group by additional config" set
                        $fieldValue = $this->getFieldData($fieldData, $updateXPath);
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
                            $fieldValue = $this->getFieldData($fieldData, $updateXPath);
                            if (!empty($fieldValue)) {
                                $rowIdentifier = $fieldValue;
                            }
                            break;
                        }
                    }
                    if (empty($rowIdentifier)) {
                        $rowIdentifier = $updateCounter;
                    }
                }
                if ($skipRow) {
                    $rowIdentifier .= '_SKIP';
                }
                if (!isset($updatesToProcess[$rowIdentifier])) {
                    $updatesToProcess[$rowIdentifier] = [];
                }

                $mappingFields = $this->mappingModel->getMapping();

                $rowArray = [];

                $nestedGroups = [];
                foreach ($mappingFields as $fieldId => $fieldData) {
                    #var_dump($fieldData);
                    if (isset($fieldData['config']['nested_xpath']) && isset($fieldData['config']['nested_xpath']['@']) && isset($fieldData['group'])) {
                        array_push($nestedGroups, $fieldData['group']);
                    }
                }
                $nestedGroups = array_unique($nestedGroups);

                // Fetch groups, grouped first, for example "tracks", "items" for nested nodes
                if (!empty($nestedGroups)) {
                    $groupRowCounter = 0;
                    foreach ($nestedGroups as $nestedGroup) {
                        $groupRowCounter++;
                        foreach ($mappingFields as $fieldId => $fieldData) {
                            if (isset($fieldData['disabled']) && $fieldData['disabled']) {
                                continue;
                            }
                            if (!isset($fieldData['config']['nested_xpath']) || !isset($fieldData['config']['nested_xpath']['@']) || !isset($fieldData['group'])) {
                                continue;
                            }
                            $currentGroup = $fieldData['group'];
                            if ($currentGroup != $nestedGroup) {
                                continue;
                            }
                            $fieldName = $fieldData['field'];
                            if (!isset($rowArray[$currentGroup])) {
                                $rowArray[$currentGroup] = [];
                            }
                            if (isset($fieldData['config']['nested_xpath']['@']['xpath'])) {
                                $nestedNodes = $updateXPath->query($fieldData['config']['nested_xpath']['@']['xpath']);
                                $nodeCounter = 0;
                                foreach ($nestedNodes as $nestedNode) {
                                    $nodeCounter++;
                                    // Nested data, init "sub dom"
                                    $nestedDOM = new \DomDocument;
                                    $nestedDOM->appendChild($nestedDOM->importNode($nestedNode, true));
                                    $nestedXPath = new \DOMXPath($nestedDOM);
                                    // Row identifier: Unique number
                                    $arrayRowIdentifier = $updateCounter . $groupRowCounter . $nodeCounter;
                                    if (!isset($rowArray[$currentGroup][$arrayRowIdentifier])) {
                                        $rowArray[$currentGroup][$arrayRowIdentifier] = [];
                                    }
                                    // Get field value
                                    $fieldValue = $this->getFieldData($fieldData, $nestedXPath);
                                    #var_dump($arrayRowIdentifier, $fieldName, $fieldValue);
                                    if ($fieldValue !== '') {
                                        if (!in_array($fieldName, $foundFields)) {
                                            $foundFields[] = $fieldName;
                                        }
                                        // Import SKU1:QTY1;SKU2:QTY2;... format
                                        if ($fieldName == 'sku' && isset($fieldData['config']['sku_qty_one_field']) && $fieldData['config']['sku_qty_one_field'] == 1) {
                                            // We're supposed to import the SKU and Qtys all from one field. Each combination separated by a ; and sku/qty separated by :
                                            $skuAndQtys = explode(";", $fieldValue);
                                            foreach ($skuAndQtys as $skuAndQty) {
                                                $nodeCounter++;
                                                $arrayRowIdentifier = $updateCounter . $groupRowCounter . $nodeCounter;
                                                if (!isset($rowArray[$currentGroup][$arrayRowIdentifier])) {
                                                    $rowArray[$currentGroup][$arrayRowIdentifier] = [];
                                                }
                                                list ($sku, $qty) = explode(":", $skuAndQty);
                                                if ($sku !== '') {
                                                    $rowArray[$currentGroup][$arrayRowIdentifier]['sku'] = $sku;
                                                    $rowArray[$currentGroup][$arrayRowIdentifier]['qty'] = $qty;
                                                }
                                            }
                                        } else {
                                            // Normal field - not SKU/QTY, not combined into one field
                                            if (empty($currentGroup)) {
                                                // Fields without group that can be looped such as order_status_history_comment
                                                if (isset($rowArray[$fieldName])) {
                                                    $rowArray[$fieldName] .= ", " . $this->mappingModel->formatField($fieldName, $fieldValue);
                                                } else {
                                                    $rowArray[$fieldName] = $this->mappingModel->formatField($fieldName, $fieldValue);
                                                }
                                            } else {
                                                if (strstr($fieldName, '|')) {
                                                    $splitFieldName = explode("|", $fieldName);
                                                    $fieldName = array_pop($splitFieldName);
                                                }
                                                $rowArray[$currentGroup][$arrayRowIdentifier][$fieldName] = $this->mappingModel->formatField(
                                                    $fieldName,
                                                    $fieldValue
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Non-nested fields
                foreach ($mappingFields as $fieldId => $fieldData) {
                    if (isset($fieldData['disabled']) && $fieldData['disabled']) {
                        continue;
                    }
                    $fieldName = $fieldData['field'];
                    if (!isset($fieldData['config']['nested_xpath'])) {
                        // No nested data
                        $fieldValue = $this->getFieldData($fieldData, $updateXPath);
                        if ($fieldValue !== '') {
                            if (!in_array($fieldName, $foundFields)) {
                                $foundFields[] = $fieldName;
                            }
                            if (isset($fieldData['group']) && !empty($fieldData['group'])) {
                                if (strstr($fieldName, '|')) {
                                    $splitFieldName = explode("|", $fieldName);
                                    $fieldName = array_pop($splitFieldName);
                                }
                                // Import SKU1:QTY1;SKU2:QTY2;... format
                                if ($fieldName == 'sku' && isset($fieldData['config']['sku_qty_one_field']) && $fieldData['config']['sku_qty_one_field'] == 1) {
                                    // We're supposed to import the SKU and Qtys all from one field. Each combination separated by a ; and sku/qty separated by :
                                    $skuAndQtys = explode(";", $fieldValue);
                                    foreach ($skuAndQtys as $skuAndQty) {
                                        $updateCounter++;
                                        list ($sku, $qty) = explode(":", $skuAndQty);
                                        if ($sku !== '') {
                                            $rowArray[$fieldData['group']][$updateCounter - 1]['sku'] = $sku;
                                            $rowArray[$fieldData['group']][$updateCounter - 1]['qty'] = $qty;
                                        }
                                    }
                                } else {
                                    // Normal field - not SKU/QTY, not combined into one field
                                    $rowArray[$fieldData['group']][$updateCounter - 1][$fieldName] = $this->mappingModel->formatField(
                                        $fieldName,
                                        $fieldValue
                                    );
                                }
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
                }
                if ($skipRow) {
                    // Field in field_configuration XML determined that this row should be skipped. "<skip>" parameter in XML field config
                    $rowArray['SKIP_FLAG'] = true;
                }
                if (isset($updatesToProcess[$rowIdentifier]) && !empty($updatesToProcess[$rowIdentifier])) {
                    $rowArray = $this->array_merge_recursive_distinct($updatesToProcess[$rowIdentifier], $rowArray);
                }

                // Combine items
                /*if (isset($rowArray['items']) && !empty($rowArray['items'])) {
                    $rowItems = [];
                    foreach ($rowArray['items'] as $arrayRowIdentifier => $fieldData) {
                        if (!empty($fieldData) && isset($fieldData['order_item|sku'])) {
                            if (isset($rowItems[$fieldData['order_item|sku']])) {
                                $fieldData['order_item|qty'] += $rowItems[$fieldData['order_item|sku']]['order_item|qty'];
                            }
                            $rowItems[$fieldData['order_item|sku']] = $fieldData;
                        }
                    }
                    $rowArray['items'] = $rowItems;
                }*/

                // Add row array to updates
                $updatesToProcess[$rowIdentifier] = $rowArray;
            }

            // File processed
            $updatesInFilesToProcess[] = [
                "FILE_INFORMATION" => $importFile,
                "FIELDS" => $foundFields,
                "ROWS" => $updatesToProcess
            ];
        }

        #ini_set('xdebug.var_display_max_depth', 10);
        #Zend_Debug::dump($updatesInFilesToProcess);
        #die();

        return $updatesInFilesToProcess;
    }

    protected function runCurrentUntilString($array)
    {
        // Run the current function on the returned SimpleXMLElement until a string (just no array!) gets returned
        $runCount = 0;
        while (true) {
            if ($array instanceof \DOMElement && isset($array->nodeValue)) {
                return $array->nodeValue;
            }
            if (is_object($array) && $array instanceof \DOMNodeList) {
                $continue = false;
                foreach ($array as $node) {
                    $array = $node;
                    $continue = true;
                }
                if ($continue) {
                    continue;
                }
            }
            if (is_object($array) && $array instanceof \DOMAttr) {
                if (isset($array->value) && $array->value !== '') {
                    return $array->value;
                }
            }
            if (is_array($array) || is_object($array)) {
                $array = current($array);
            } else {
                return $array;
            }
            $runCount++;
            if ($runCount > 15) {
                // Do not run this loop too often.
                return '';
            }
        }
    }

    /**
     * Wrapper function to manipulate field data returned
     *
     * @param $fieldData
     * @param $updateXPath
     *
     * @return string
     */
    public function getFieldData($fieldData, $updateXPath)
    {
        $returnData = $this->getFieldDataRaw($fieldData, $updateXPath);
        $returnData = $this->fieldsConfiguration->manipulateFieldFetched(
            $fieldData['field'],
            $returnData,
            $fieldData['config'],
            $this
        );
        return $returnData;
    }

    public function getFieldDataRaw($fieldData, $updateXPath)
    {
        $field = $fieldData['field'];
        if ($fieldData['value'] != '') {
            if (!is_object($updateXPath) && $updateXPath == 1) {
                $updateXPath = $this->rowData;
            }
            $data = $this->runCurrentUntilString($updateXPath->query($fieldData['value']));
            $data = $this->fieldsConfiguration->handleField($field, $data, $fieldData['config']);
            /*
             * Alternate method to pull fields, when xpath fails.
             */
            if ($data === '' || $data === null || $data === false) {
                foreach ($updateXPath as $key => $value) {
                    if ($key == $fieldData['value']) {
                        $data = (string)$value;
                        $data = $this->fieldsConfiguration->handleField($field, $data, $fieldData['config']);
                        break 1;
                    }
                }
            }
            if (($data === '' || $data === null || $data === false) && isset($fieldData['id'])) {
                // Try to get the default value at least.. otherwise ''
                $data = $this->mappingModel->getDefaultValue($fieldData['id']);
            }
        } else {
            $data = $this->fieldsConfiguration->handleField($field, '', $fieldData['config']);
            if (empty($data) && isset($fieldData['id'])) {
                // Try to get the default value at least.. otherwise ''
                $data = $this->mappingModel->getDefaultValue($fieldData['id']);
            }
        }
        return trim($data);
    }

    protected function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
