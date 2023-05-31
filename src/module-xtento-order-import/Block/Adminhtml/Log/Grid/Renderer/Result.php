<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Log/Grid/Renderer/Result.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Log\Grid\Renderer;

class Result extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getResult() === null || $row->getResult() == 0) {
            return '<span class="grid-severity-major"><span>' . __('No Result') . '</span></span>';
        } else {
            if ($row->getResult() == 1) {
                return '<span class="grid-severity-notice"><span>' . __('Success') . '</span></span>';
            } else {
                if ($row->getResult() == 2) {
                    return '<span class="grid-severity-minor"><span>' . __('Warning') . '</span></span>';
                } else {
                    if ($row->getResult() == 3) {
                        return '<span class="grid-severity-critical"><span>' . __('Failed') . '</span></span>';
                    }
                }
            }
        }
        return '';
    }
}
