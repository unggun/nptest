<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-04-08T10:53:34+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/History/Grid/Renderer/Status.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\History\Grid\Renderer;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getStatus() === null || $row->getStatus() == 0) {
            return '<span><span>' . __('Unknown') . '</span></span>';
        } else {
            if ($row->getStatus() == 1) {
                return '<span class="grid-severity-notice"><span>' . __('Success') . '</span></span>';
            } else {
                if ($row->getStatus() == 2) {
                    return '<span class="grid-severity-minor"><span>' . __('Warning') . '</span></span>';
                } else {
                    if ($row->getStatus() == 3) {
                        return '<span class="grid-severity-critical"><span>' . __('Failed') . '</span></span>';
                    }
                }
            }
        }
        return '';
    }
}
