<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-03-31T19:42:32+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Mapping/Fields/Configuration.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor\Mapping\Fields;

use Xtento\OrderImport\Model\Processor\Mapping\AbstractConfiguration;

class Configuration extends AbstractConfiguration
{
    protected $configurationType = 'fields';

    public function handleField($field, $value, $fieldConfiguration)
    {
        // Mapped fields
        if (isset($fieldConfiguration['map'])) {
            if (count($fieldConfiguration['map']) > 1) {
                // Multiple mapping values
                foreach ($fieldConfiguration['map'] as $config) {
                    $value = $this->mapFromTo($field, $value, $config);
                }
            } else {
                // One mapping value
                $config = $fieldConfiguration['map'];
                $value = $this->mapFromTo($field, $value, $config);
            }
        }
        // Search & Replace
        if (isset($fieldConfiguration['replace'])) {
            if (count($fieldConfiguration['replace']) > 1) {
                // Multiple mapping values
                foreach ($fieldConfiguration['replace'] as $config) {
                    $value = $this->searchReplace($field, $value, $config);
                }
            } else {
                // One mapping value
                $config = $fieldConfiguration['replace'];
                $value = $this->searchReplace($field, $value, $config);
            }
        }
        // Arithmetic operations
        if (isset($fieldConfiguration['calculate'])) {
            $config = $fieldConfiguration['calculate'];
            $value = $this->arithmeticOperation($field, $value, $config);
        }
        return $value;
    }

    /*
     * Map "from" > "to" - mappings for fields
     */
    protected function mapFromTo($field, $value, $config)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['from']) && isset($configAttributes['to'])) {
                // Matching method
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($configAttributes['from'] == $value) {
                        $value = $configAttributes['to'];
                    }
                } else {
                    if (trim($configAttributes['method']) == 'preg_match') {
                        // preg_match
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace(
                                "e",
                                "",
                                $configAttributes['regex_modifier']
                            );
                        }
                        $expectedResult = 1;
                        if (isset($configAttributes['negate']) && $configAttributes['negate'] == 'true') {
                            $expectedResult = 0; // Should return 0 if no match found
                        }
                        if (preg_match(
                            '/' . str_replace(
                                '/',
                                '\\/',
                                $configAttributes['from']
                            ) . '/' . $configAttributes['regex_modifier'],
                            $value
                        ) === $expectedResult) {
                            $value = $configAttributes['to'];
                        }
                    }
                }
            }
        }
        return $value;
    }

    protected function searchReplace($field, $value, $config)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['search']) && isset($configAttributes['replace'])) {
                // Matching method
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'str_replace')) {
                    // No method specified, str_replace
                    $value = str_replace($configAttributes['search'], $configAttributes['replace'], $value);
                } else {
                    if (trim($configAttributes['method']) == 'preg_replace') {
                        // preg_replace
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace(
                                "e",
                                "",
                                $configAttributes['regex_modifier']
                            );
                        }
                        $value = preg_replace(
                            '/' . str_replace(
                                '/',
                                '\\/',
                                $configAttributes['search']
                            ) . '/' . $configAttributes['regex_modifier'],
                            $configAttributes['replace'],
                            $value
                        );
                    }
                }
            }
        }
        return $value;
    }

    /*
     * Arithmetic operations such as + - / *
     */
    protected function arithmeticOperation($field, $value, $config)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['value']) && is_numeric($value)) {
                $operationValue = (float)$configAttributes['value'];
                if (!is_numeric($operationValue)) {
                    return $value;
                }
                if ($configAttributes['operation'] === '+') {
                    $value = $value + $operationValue;
                }
                if ($configAttributes['operation'] === '-') {
                    $value = max(0, $value - $operationValue); // Shouldn't become negative
                }
                if ($configAttributes['operation'] === '*') {
                    $value = $value * $operationValue;
                }
                if ($configAttributes['operation'] === '/') {
                    $value = $value / $operationValue;
                }
            }
        }
        return $value;
    }

    /*
     * Manipulate the field that is loaded from the file using "use" parameter
     */
    public function manipulateFieldFetched($field, $value, $fieldConfiguration, $processorClass)
    {
        // Use fields based on conditions
        if (isset($fieldConfiguration['use'])) {
            if (count($fieldConfiguration['use']) > 1) {
                // Multiple "use" values
                foreach ($fieldConfiguration['use'] as $config) {
                    $value = $this->matchManipulatedField($field, $value, $config, $processorClass);
                }
            } else {
                // One "use" value
                $config = $fieldConfiguration['use'];
                $value = $this->matchManipulatedField($field, $value, $config, $processorClass);
            }
        }
        if (isset($fieldConfiguration['calculate_sum_from_fields'])) {
            if (count($fieldConfiguration['calculate_sum_from_fields']) > 1) {
                // Multiple "sum" values
                foreach ($fieldConfiguration['calculate_sum_from_fields'] as $config) {
                    $value = $this->calculateSumFromFields($field, $value, $config, $processorClass);
                }
            } else {
                // One "sum" value
                $config = $fieldConfiguration['calculate_sum_from_fields'];
                $value = $this->calculateSumFromFields($field, $value, $config, $processorClass);
            }
        }
        if (isset($fieldConfiguration['divide_by_field'])) {
            if (count($fieldConfiguration['divide_by_field']) > 1) {
                // Multiple "divide_by" values
                foreach ($fieldConfiguration['divide_by_field'] as $config) {
                    $value = $this->divideByField($field, $value, $config, $processorClass);
                }
            } else {
                // One "divide_by" value
                $config = $fieldConfiguration['divide_by_field'];
                $value = $this->divideByField($field, $value, $config, $processorClass);
            }
        }
        return $value;
    }

    protected function matchManipulatedField($field, $value, $config, $processorClass)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['field']) && isset($configAttributes['if']) && isset($configAttributes['is'])) {
                // Matching method
                $matchValue = $processorClass->getFieldDataRaw(
                    ['field' => $field, 'value' => $configAttributes['if'], 'config' => []],
                    true
                );
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($matchValue == $configAttributes['is']) { // If field "if" is "is" then use "field"
                        $value = $processorClass->getFieldDataRaw(
                            ['field' => $field, 'value' => $configAttributes['field'], 'config' => []],
                            true
                        ); // Use field "value" then
                    }
                } else {
                    if (trim($configAttributes['method']) == 'preg_match') {
                        // preg_match
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace(
                                "e",
                                "",
                                $configAttributes['regex_modifier']
                            );
                        }
                        $expectedResult = 1;
                        if (isset($configAttributes['negate']) && $configAttributes['negate'] == 'true') {
                            $expectedResult = 0; // Should return 0 if no match found
                        }
                        if (preg_match(
                            '/' . str_replace(
                                '/',
                                '\\/',
                                $configAttributes['is']
                            ) . '/' . $configAttributes['regex_modifier'],
                            $matchValue
                        ) === $expectedResult) {
                            $value = $processorClass->getFieldDataRaw(
                                ['field' => $field, 'value' => $configAttributes['field'], 'config' => []],
                                true
                            ); // Use field "value" then
                        }
                    }
                }
            }
        }
        return $value;
    }

    // <calculate_sum_from_fields add_from_field="Warehouse ABC"/>
    protected function calculateSumFromFields($field, $value, $config, $processorClass)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['add_from_field'])) {
                // Matching method
                $value += $processorClass->getFieldDataRaw(
                    ['field' => $field, 'value' => $configAttributes['add_from_field'], 'config' => []],
                    true
                );
            }
        }
        return $value;
    }

    // <divide_by_field field="some_field_node"/>
    protected function divideByField($field, $value, $config, $processorClass)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['field'])) {
                $otherFieldValue = $processorClass->getFieldDataRaw(
                    ['field' => $field, 'value' => $configAttributes['field'], 'config' => []],
                    true
                );
                if ($otherFieldValue == 0) {
                    $otherFieldValue = 1;
                }
                $value = $value / $otherFieldValue;
            }
        }
        return $value;
    }

    /*
     * If "skip" node is set in XML configuration, check if this row in the import file should be skipped based on "if" and "is" attributes
     */
    public function checkSkipImport($field, $fieldConfiguration, $processorClass)
    {
        $skipImport = false;
        // Check if import of current row should be skipped
        if (isset($fieldConfiguration['skip'])) {
            if (count($fieldConfiguration['skip']) > 1) {
                // Multiple skip values
                foreach ($fieldConfiguration['skip'] as $config) {
                    if ($this->skipCheck($field, $config, $processorClass)) {
                        $skipImport = true;
                    }
                }
            } else {
                // One skip value
                $config = $fieldConfiguration['skip'];
                if ($this->skipCheck($field, $config, $processorClass)) {
                    $skipImport = true;
                }
            }
        }
        return $skipImport;
    }

    protected function skipCheck($field, $config, $processorClass)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['if']) && isset($configAttributes['is'])) {
                // Matching method
                $matchValue = $processorClass->getFieldDataRaw(
                    ['field' => $field, 'value' => $configAttributes['if'], 'config' => []],
                    true
                );
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($matchValue == $configAttributes['is']) { // If field "if" is "is" then use "field"
                        return true;
                    }
                } else {
                    if (trim($configAttributes['method']) == 'preg_match') {
                        // preg_match
                        if (!isset($configAttributes['regex_modifier'])) {
                            $configAttributes['regex_modifier'] = '';
                        } else {
                            $configAttributes['regex_modifier'] = str_replace(
                                "e",
                                "",
                                $configAttributes['regex_modifier']
                            );
                        }
                        $expectedResult = 1;
                        if (isset($configAttributes['negate']) && $configAttributes['negate'] == 'true') {
                            $expectedResult = 0; // Should return 0 if no match found
                        }
                        if (preg_match(
                            '/' . str_replace(
                                '/',
                                '\\/',
                                $configAttributes['is']
                            ) . '/' . $configAttributes['regex_modifier'],
                            $matchValue
                        ) === $expectedResult) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}
