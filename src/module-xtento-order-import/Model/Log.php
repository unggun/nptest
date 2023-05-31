<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/Log.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model;

/**
 * Class Log
 * Log model which keeps track of successful/failed import attempts
 * @package Xtento\OrderImport\Model
 */
class Log extends \Magento\Framework\Model\AbstractModel
{
    protected $resultMessages = [];
    protected $debugMessages = [];

    // Log result types
    const RESULT_NORESULT = 0;
    const RESULT_SUCCESSFUL = 1;
    const RESULT_WARNING = 2;
    const RESULT_FAILED = 3;

    protected function _construct()
    {
        $this->_init('Xtento\OrderImport\Model\ResourceModel\Log');
        $this->_collectionName = 'Xtento\OrderImport\Model\ResourceModel\Log\Collection';
    }

    public function setResult($resultLevel)
    {
        if ($this->getResult() === null) {
            $this->setData('result', $resultLevel);
        } else {
            if ($resultLevel > $this->getResult()) { // If result is failed, do not reset to warning for example.
                $this->setData('result', $resultLevel);
            }
        }
    }

    public function addResultMessage($message)
    {
        array_push($this->resultMessages, $message);
    }

    public function getResultMessages()
    {
        if (empty($this->resultMessages)) {
            return false;
        }
        return (count($this->resultMessages) > 1) ? implode("\n", $this->resultMessages) : $this->resultMessages[0];
    }

    public function addDebugMessage($message)
    {
        if ($this->getLogDebugMessages()) {
            array_push($this->debugMessages, $message);
        }
    }

    public function getDebugMessages()
    {
        if (empty($this->debugMessages)) {
            return false;
        }
        return (count($this->debugMessages) > 1) ? implode("\n", $this->debugMessages) : $this->debugMessages[0];
    }
}