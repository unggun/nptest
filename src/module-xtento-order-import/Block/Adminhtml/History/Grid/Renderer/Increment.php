<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-18T16:12:57+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/History/Grid/Renderer/Increment.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\History\Grid\Renderer;

class Increment extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render increment ID
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $incrementIdFields = ['increment_id', 'order_increment_id', 'invoice_increment_id', 'shipment_increment_id', 'creditmemo_increment_id'];
        foreach ($incrementIdFields as $incrementIdField) {
            if ($row->getData($incrementIdField) !== NULL) {
                return $row->getData($incrementIdField);
            }
        }
        return '';
    }
}
