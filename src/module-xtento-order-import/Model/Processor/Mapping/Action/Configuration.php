<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Mapping/Action/Configuration.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor\Mapping\Action;

use Xtento\OrderImport\Model\Processor\Mapping\AbstractConfiguration;

class Configuration extends AbstractConfiguration
{
    protected $configurationType = 'action';

    /*
     * If "set" node is set in XML configuration, [...]
     */
    public function setValueBasedOnFieldData($updateData, $fieldConfiguration)
    {
        $changeData = -99;
        // Check if import of current row should be skipped
        if (isset($fieldConfiguration['set'])) {
            if (count($fieldConfiguration['set']) > 1) {
                // Multiple <set> nodes
                foreach ($fieldConfiguration['set'] as $config) {
                    $changeData = $this->changeCheck($updateData, $config);
                }
            } else {
                // One <set> node
                $config = $fieldConfiguration['set'];
                $changeData = $this->changeCheck($updateData, $config);
            }
        }
        if ($changeData === 'true') {
            $changeData = true;
        }
        if ($changeData === 'false') {
            $changeData = false;
        }
        return $changeData;
    }

    protected function changeCheck($updateData, $config)
    {
        if (isset($config['@'])) {
            $configAttributes = $config['@'];
            if (isset($configAttributes['if']) && isset($configAttributes['is']) && isset($configAttributes['value'])) {
                // Matching method
                #var_dump($updateData); die();
                if (isset($updateData[$configAttributes['if']])) {
                    $matchValue = $updateData[$configAttributes['if']];
                } else {
                    $matchValue = "";
                }
                if (!isset($configAttributes['method']) || (isset($configAttributes['method']) && $configAttributes['method'] == 'equals')) {
                    // No method specified, exact matching
                    if ($matchValue == $configAttributes['is']) { // If field "if" is "is" then use "field"
                        return $configAttributes['value'];
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
                            return $configAttributes['value'];
                        }
                    }
                }
            }
        }
        return -99;
    }
}
