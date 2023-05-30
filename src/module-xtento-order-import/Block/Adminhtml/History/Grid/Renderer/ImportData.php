<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-09T13:41:14+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/History/Grid/Renderer/ImportData.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\History\Grid\Renderer;

class ImportData extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return mixed|string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $data = "<pre>" . print_r(json_decode($row->getData('import_data'), true), true) . "</pre>";

        $_id = 'id' . uniqid();
        $html = '<a href="#" onclick="return false;">' . __('Show Data') . '</a> <span id="' . $_id . '">' . $data . '</span>';
        $html .= '<script type="text/javascript">
        require(["jquery", "prototype"], function (jQuery) {
            $(\'' . $_id . '\').hide();
            $(\'' . $_id . '\').up().observe(\'mouseover\', function(){$(\'' . $_id . '\').show();});
            $(\'' . $_id . '\').up().observe(\'mouseout\',  function(){$(\'' . $_id . '\').hide();});
        });
        </script>
        ';

        return $html;
    }
}
