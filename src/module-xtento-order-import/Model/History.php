<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-04-08T10:41:48+00:00
 * File:          app/code/Xtento/OrderImport/Model/History.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model;

/**
 * Class History
 * @package Xtento\OrderImport\Model
 */
class History extends \Magento\Framework\Model\AbstractModel
{
    // Status types
    const RESULT_NORESULT = 0;
    const RESULT_SUCCESSFUL = 1;
    const RESULT_WARNING = 2;
    const RESULT_FAILED = 3;

    protected function _construct()
    {
        $this->_init('Xtento\OrderImport\Model\ResourceModel\History');
        $this->_collectionName = 'Xtento\OrderImport\Model\ResourceModel\History\Collection';
    }
}