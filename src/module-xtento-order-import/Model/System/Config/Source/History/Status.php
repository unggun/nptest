<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-04-08T10:40:48+00:00
 * File:          app/code/Xtento/OrderImport/Model/System/Config/Source/History/Status.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\System\Config\Source\History;

use Magento\Framework\Option\ArrayInterface;

/**
 * @codeCoverageIgnore
 */
class Status implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $values = [
            \Xtento\OrderImport\Model\History::RESULT_NORESULT => __('Unknown'),
            \Xtento\OrderImport\Model\History::RESULT_SUCCESSFUL => __('Successful'),
            \Xtento\OrderImport\Model\History::RESULT_WARNING => __('Warning'),
            \Xtento\OrderImport\Model\History::RESULT_FAILED => __('Failed')
        ];
        return $values;
    }
}
