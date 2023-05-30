<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-05T15:38:03+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/AbstractProcessor.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor;

use Magento\Framework\DataObject;
use Xtento\OrderImport\Logger\Logger;
use Xtento\OrderImport\Model\Processor\Mapping\Fields\Configuration;
use Xtento\OrderImport\Model\Processor\Mapping\FieldsFactory;

abstract class AbstractProcessor extends DataObject
{
    protected $mappingModel;
    protected $mapping;

    /**
     * @var FieldsFactory
     */
    protected $mappingFieldsFactory;

    /**
     * @var Configuration
     */
    protected $fieldsConfiguration;

    /**
     * @var Logger
     */
    protected $xtentoLogger;

    /**
     * AbstractProcessor constructor.
     *
     * @param FieldsFactory $mappingFieldsFactory
     * @param Configuration $fieldsConfiguration
     * @param Logger $xtentoLogger
     * @param array $data
     */
    public function __construct(
        FieldsFactory $mappingFieldsFactory,
        Configuration $fieldsConfiguration,
        Logger $xtentoLogger,
        array $data = []
    ) {
        $this->mappingFieldsFactory = $mappingFieldsFactory;
        $this->fieldsConfiguration = $fieldsConfiguration;
        $this->xtentoLogger = $xtentoLogger;

        parent::__construct($data);
    }

    protected function getConfiguration()
    {
        return $this->getProfile()->getConfiguration();
    }

    protected function getConfigValue($key)
    {
        $configuration = $this->getConfiguration();
        if (isset($configuration[$key])) {
            return $configuration[$key];
        } else {
            return false;
        }
    }
}