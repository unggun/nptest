<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:11:24+00:00
 * File:          app/code/Xtento/OrderImport/Model/Source/Httpdownload.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Source;

use Magento\Framework\DataObject;

class Httpdownload extends AbstractClass
{
    public function testConnection()
    {
        $testResult = $this->initConnection();
        return $testResult;
    }

    public function initConnection()
    {
        $this->setSource($this->sourceFactory->create()->load($this->getSource()->getId()));
        $testResult = new DataObject();
        $testResult->setSuccess(true)->setMessage(__('HTTP Download class initialized successfully.'));
        $this->getSource()->setLastResult($testResult->getSuccess())->setLastResultMessage($testResult->getMessage())->save();
        return $testResult;
    }

    public function loadFiles()
    {
        // Init connection
        $this->initConnection();

        $url = $this->getSource()->getCustomFunction();
        $useBasicAuth = false;
        $username = '';
        $password = '';
        // Parse URL for username + password
        $parsedUrl = parse_url($url);
        if ($parsedUrl !== false) {
            if ($parsedUrl['scheme'] == 'http' || $parsedUrl['scheme'] == 'https') {
                if (array_key_exists('user', $parsedUrl) && $parsedUrl['user'] !== '') {
                    $url = str_replace($parsedUrl['user'] . ':', '', $url); // Update URL
                    $username = urldecode($parsedUrl['user']);
                    $useBasicAuth = true;
                }
                if (array_key_exists('pass', $parsedUrl) && $parsedUrl['pass'] !== '') {
                    $url = str_replace($parsedUrl['pass'] . '@', '', $url); // Update URL
                    $password = urldecode($parsedUrl['pass']);
                    $useBasicAuth = true;
                }
            }
        }

        $curlClient = curl_init();
        curl_setopt($curlClient, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlClient, CURLOPT_HEADER, false);
        curl_setopt($curlClient, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlClient, CURLOPT_URL, $url);
        if ($useBasicAuth) {
            curl_setopt($curlClient, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($curlClient, CURLOPT_USERPWD, $username . ":" . $password);
        }
        curl_setopt($curlClient, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curlClient);

        if ($result === false) {
            // curl_error
            $logEntry = $this->_registry->registry('orderimport_log');
            $logEntry->setResult(\Xtento\OrderImport\Model\Log::RESULT_WARNING);
            $logEntry->addResultMessage(__('Source "%1" (ID: %2): There was a problem downloading the file "%3", probably a firewall is blocking the connection: curl_error: %4', $this->getSource()->getName(), $this->getSource()->getId(), $this->getSource()->getCustomFunction(), curl_error($curlClient)));
        }

        curl_close($curlClient);

        $filesToProcess[] = ['source_id' => $this->getSource()->getId(), 'path' => '', 'filename' => basename($this->getSource()->getCustomFunction()), 'data' => $result];

        // Return files to process
        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false)
    {

    }
}