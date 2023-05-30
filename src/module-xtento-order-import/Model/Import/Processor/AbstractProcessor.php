<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-05T15:38:35+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/AbstractProcessor.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor;

use Magento\Framework\DataObject;
use Xtento\OrderImport\Model\Import;

abstract class AbstractProcessor extends DataObject
{
    protected function isCreateMode($updateData)
    {
        return ($this->getImportMode(
                $updateData
            ) == Import::IMPORT_MODE_NEW || $this->getImportMode(
                $updateData
            ) == Import::IMPORT_MODE_NEWUPDATE);
    }

    protected function isDeleteMode($updateData)
    {
        return $this->getImportMode($updateData) == Import::IMPORT_MODE_DELETE;
    }

    protected function isUpdateMode($updateData)
    {
        return ($this->getImportMode(
                $updateData
            ) == Import::IMPORT_MODE_UPDATE || $this->getImportMode(
                $updateData
            ) == Import::IMPORT_MODE_NEWUPDATE);
    }

    protected function getImportMode($updateData)
    {
        return $updateData['__importMode'];
    }

    protected function getRowIdentifier($updateData)
    {
        return $updateData['__rowIdentifier'];
    }

    protected function isObjectNew($updateData, $entity)
    {
        if (!isset($updateData[$entity])) {
            return true;
        }
        return $updateData[$entity]['__isObjectNew'];
    }

    protected function isOrderNew($orderData)
    {
        return $orderData['__isObjectNew'];
    }

    /*
    * Return configuration value
    */
    function getConfig($key)
    {
        $configuration = $this->getProfile()->getConfiguration();
        if (isset($configuration[$key])) {
            return $configuration[$key];
        } else {
            return false;
        }
    }

    function getConfigFlag($key)
    {
        return (bool)$this->getConfig($key);
    }


    // Abstract functions to validate/process row
    public function validate(&$updateData)
    {
        return true;
    }

    public function process(&$order, &$updateData)
    {
        return true;
    }
}