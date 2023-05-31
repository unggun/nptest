<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/System/Config/Source/Log/Result.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\System\Config\Source\Log;

use Magento\Framework\Option\ArrayInterface;

/**
 * @codeCoverageIgnore
 */
class Result implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $values = [
            \Xtento\OrderImport\Model\Log::RESULT_NORESULT => __('No Result'),
            \Xtento\OrderImport\Model\Log::RESULT_SUCCESSFUL => __('Successful'),
            \Xtento\OrderImport\Model\Log::RESULT_WARNING => __('Warning'),
            \Xtento\OrderImport\Model\Log::RESULT_FAILED => __('Failed')
        ];
        return $values;
    }
}
