<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-18T15:43:12+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Mapping/Importfields.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping;

use Magento\Framework\View\Element\AbstractBlock;

class Importfields extends AbstractBlock
{
    public function _toHtml()
    {
        $htmlId = 'select_#{_id}';
        $html = '<select id="' . $htmlId . '" name="' . $this->getInputName(
            ) . '" class="select" style="' . $this->getStyle() . '" onchange="' . $this->_getSelectOnChangeJs(
            ) . '" onmouseover="' . $this->_getSelectBeforeClickJs() . '">' . $this->_getImportFields() . '<\\/select>';
        $html .= '&nbsp;<a href="#" onclick="helpPopup = $(\\\'' . $htmlId . '\\\').options[$(\\\'' . $htmlId . '\\\').selectedIndex].getAttribute(\\\'tooltip\\\'); if (helpPopup == null) { helpPopup = \\\'' . __(
                'No description available.'
            ) . '\\\'; } alert(helpPopup);" onmouseout=""><img src="' . $this->getViewFileUrl('Xtento_OrderImport::images/help.png') . '" style="vertical-align: middle;"/></a>';

        // Select the pre-mapped field
        $html .= <<<JS
        <script>
            if ({$this->getMappingId()}_mapping_values[\'#{_id}\']) {
                $(\'{$htmlId}\').setValue({$this->getMappingId()}_mapping_values[\'#{_id}\']);
            }

            if ($(\'{$htmlId}\').options[$(\'{$htmlId}\').selectedIndex].hasClassName(\'default-value-disabled\') && $(\'{$this->getMappingId(
        )}[#{_id}][default_value]\')) {
                $(\'{$this->getMappingId()}[#{_id}][default_value]\').value = \'\';
                $(\'{$this->getMappingId()}[#{_id}][default_value]\').disable();
                $(\'{$this->getMappingId()}[#{_id}][default_value]\').style.backgroundColor = \'#f0f0f0\';
            }

            if ($(\'{$this->getMappingId()}DivOverlay\')) $(\'{$this->getMappingId()}DivOverlay\').hide();
            if (!$(\'{$this->getMappingId()}_save_data\')) {
                input = document.createElement(\'input\');
                input.setAttribute(\'type\', \'hidden\');
                input.setAttribute(\'name\', \'{$this->getMappingId()}[__save_data]\');
                input.setAttribute(\'id\', \'{$this->getMappingId()}_save_data\');
                input.setAttribute(\'value\', \'1\');
                $(\'grid_{$this->getMappingId()}\').appendChild(input);
            }
        <\/script>
JS;

        return str_replace(["\r", "\n", "\r\n"], "", $html);
    }

    public function _getImportFields()
    {
        $html = '<option value="" selected="selected">' . $this->getSelectLabel() . '<\\/option>';
        $currentOptgroup = '';
        foreach ($this->getImportFields() as $code => $field) {
            $disabled = '';
            if (isset($field['start_optgroup']) && $field['start_optgroup']) {
                $currentOptgroup = $field['label'];
                if (strstr($field['label'], '(') !== false) {
                    $currentOptgroup = substr($currentOptgroup, 0, strpos($currentOptgroup, '(') - 1);
                }
            }
            if (isset($field['stop_optgroup']) && $field['stop_optgroup']) {
                $currentOptgroup = '';
                continue;
            }
            if (isset($field['disabled']) && $field['disabled']) {
                $disabled = ' disabled="disabled"';
            }
            if (isset($field['tooltip']) && $field['tooltip']) {
                $toolTip = str_replace(["\r", "\n", "\r\n"], "", $field['tooltip']);
            } else {
                $toolTip = __('No help/description available.');
            }
            $tooltipJs = ' tooltip="' . $toolTip . '"';
            $className = '';
            if (isset($field['default_value_disabled']) && $field['default_value_disabled']) {
                $className = ' class="default-value-disabled"';
            }
            $groupDescription = '';
            if (!empty($currentOptgroup) && !isset($field['start_optgroup'])) {
                $groupDescription = ' ('.$currentOptgroup.')';
            }
            $html .= '<option value="' . $code . '"' . $disabled . $tooltipJs . $className . '>' . $field['label'] . $groupDescription . '<\\/option>';
        }
        return $html;
    }

    public function _getSelectOnChangeJs()
    {
        $selectDefaultValueForActionsJs = "";
        if ($this->getMappingId() == 'action') {
            $selectDefaultValueForActionsJs = "if (pair.key == \'1\') option.setAttribute(\'selected\', \'selected\');";
        }
        $inputNameDefaultValues = str_replace('[field]', '[default_value]', $this->getInputName());
        $js = <<<JS
if ($(\'{$this->getMappingId()}[#{_id}][default_value]\')) {
    if (this.options[this.selectedIndex].hasClassName(\'default-value-disabled\')) {
        $(\'{$this->getMappingId()}[#{_id}][default_value]\').value = \'\';
        $(\'{$this->getMappingId()}[#{_id}][default_value]\').disable();
        $(\'{$this->getMappingId()}[#{_id}][default_value]\').style.backgroundColor = \'#f0f0f0\';
    } else {
        $(\'{$this->getMappingId()}[#{_id}][default_value]\').disabled = false;
        $(\'{$this->getMappingId()}[#{_id}][default_value]\').style.backgroundColor = \'#fff\';
    }
}
{$this->getMappingId()}_mapping_values[\'#{_id}\'] = this.value;

var default_values = {$this->getMappingId()}_possible_default_values.get(this.value);
if (default_values) {
    if ($(\'{$this->getMappingId()}[#{_id}][default_value]\')) {
        var field = $(\'{$this->getMappingId()}[#{_id}][default_value]\').parentNode;
    } else if ($(\'select_default_#{_id}\')) {
        var field = $(\'select_default_#{_id}\').parentNode;
    } else {
        return;
    }
    field.innerHTML = \'\';
    select = document.createElement(\'select\');
    select.setAttribute(\'style\', \'width: 98%;\');
    select.setAttribute(\'id\', \'select_default_#{_id}\');
    select.setAttribute(\'name\', \'{$inputNameDefaultValues}\');
    select.setAttribute(\'class\', \'select\');
    option = document.createElement(\'option\');
    optionText = document.createTextNode(\'--- Select value ---\');
    option.appendChild(optionText);
    option.setAttribute(\'value\', \'\');
    select.appendChild(option);
    \$H(default_values).each(function(pair) {
        option = document.createElement(\'option\');
        optionText = document.createTextNode(pair.value);
        option.appendChild(optionText);
        option.setAttribute(\'value\', pair.key);
        {$selectDefaultValueForActionsJs}
        select.appendChild(option);
    });
    field.appendChild(select);
} else {
    if ($(\'select_default_#{_id}\')) {
        var field = $(\'select_default_#{_id}\').parentNode;
        field.innerHTML = \'\';
        input = document.createElement(\'input\');
        input.setAttribute(\'type\', \'text\');
        input.setAttribute(\'id\', \'{$inputNameDefaultValues}\');
        input.setAttribute(\'name\', \'{$inputNameDefaultValues}\');
        input.setAttribute(\'value\', \'\');
        input.setAttribute(\'class\', \'input-text\');
        input.setAttribute(\'style\', \'width: 98%;\');
        field.appendChild(input);
    }
}
JS;
        return str_replace(["\r", "\n", "\r\n"], "", $js);
    }


    public function _getSelectBeforeClickJs()
    {
        $js = <<<JS
  for (i=0; i<this.options.length; i++) {
    if (this.options[i].innerHTML.include(\'-- \') || this.value == this.options[i].value) {
        continue;
    }
    var hasValue = false;
    for (var i2 in {$this->getMappingId()}_mapping_values) {
        if ({$this->getMappingId()}_mapping_values[i2] == this.options[i].value) {
            hasValue = true;
        }
    }
    if (hasValue) {
        /*$(this.options[i]).disabled = true;*/
    } else {
        /*$(this.options[i]).disabled = false;*/
    }
  }
JS;
        return str_replace(["\r", "\n", "\r\n"], "", $js);
    }
}
