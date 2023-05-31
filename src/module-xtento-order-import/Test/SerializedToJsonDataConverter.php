<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Test/SerializedToJsonDataConverter.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Test;

use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Serializer used to convert data to JSON
 *
 * This class is not a test. We had to place it here to avoid code compilation on pre-2.2 systems, where the implemented interface doesn't exist.
 */
class SerializedToJsonDataConverter implements \Magento\Framework\DB\DataConverter\DataConverterInterface
{
    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * @var Json
     */
    private $json;

    /**
     * Constructor
     *
     * @param Serialize $serialize
     * @param Json $json
     */
    public function __construct(
        Serialize $serialize,
        Json $json
    ) {
        $this->serialize = $serialize;
        $this->json = $json;
    }

    /**
     * Convert from serialized to JSON format
     *
     * @param string $value
     *
     * @return string
     */
    public function convert($value)
    {
        $isSerialized = $this->isSerialized($value);
        if (!$isSerialized) {
            return $value;
        }
        $unserialized = $this->serialize->unserialize($value);
        return $this->json->serialize($unserialized);
    }

    /**
     * Check if value is serialized string
     *
     * @param string $value
     *
     * @return boolean
     */
    public function isSerialized($value)
    {
        if (is_array($value)) {
            return false;
        }
        return (boolean)preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }
}