<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-04T11:46:13+00:00
 * File:          app/code/Xtento/OrderImport/Model/System/Config/Source/Import/Customer.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\System\Config\Source\Import;

use Magento\Framework\Option\ArrayInterface;

/**
 * @codeCoverageIgnore
 */
class Customer implements ArrayInterface
{
    /**
     * @var \Xtento\OrderImport\Model\Import
     */
    protected $importModel;

    /**
     * Entity constructor.
     *
     * @param \Xtento\OrderImport\Model\Import $importModel
     */
    public function __construct(\Xtento\OrderImport\Model\Import $importModel)
    {
        $this->importModel = $importModel;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->importModel->getCustomerCreationModes();
    }
}
