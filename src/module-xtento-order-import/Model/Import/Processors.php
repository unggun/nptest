<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-18T15:23:48+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processors.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import;

use Magento\Framework\DataObject;
use Xtento\OrderImport\Model\Import;

class Processors extends DataObject
{
    public function getProcessorClasses($entity)
    {
        //@todo: merge custom processors
        if ($entity == Import::ENTITY_ORDER) {
            return [
                '\Xtento\OrderImport\Model\Import\Processor\Order'
            ];
        }
        if ($entity == Import::ENTITY_CUSTOMER) {
            return [
                '\Xtento\OrderImport\Model\Import\Processor\Customer'
            ];
        }
        return [];
    }
}