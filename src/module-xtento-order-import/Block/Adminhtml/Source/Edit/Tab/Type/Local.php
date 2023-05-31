<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Source/Edit/Tab/Type/Local.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Source\Edit\Tab\Type;

class Local extends AbstractType
{
    // Local Directory Configuration
    public function getFields(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'config_fieldset',
            [
                'legend' => __('Local Directory Configuration'),
            ]
        );

        $fieldset->addField(
            'path',
            'text',
            [
                'label' => __('Import Directory'),
                'name' => 'path',
                'note' => __(
                    'Path to the directory where import files will be searched in. Use an absolute path or specify a path relative to the Magento root directory by putting a dot at the beginning. Example to import from the var/import/ directory located in the root directory of Magento: ./var/import/  Example to import from an absolute directory: /var/www/test/ would import from the absolute path /var/www/test (and not located in the Magento installation)'
                ),
                'required' => true
            ]
        );
        $fieldset->addField(
            'filename_pattern',
            'text',
            [
                'label' => __('Filename Pattern'),
                'name' => 'filename_pattern',
                'note' => __(
                    'This needs to be a valid regular expression. The regular expression will be used to detect import files. The import will fail if the pattern is invalid. Example: /csv/i for all files with the csv file extension or for all files in the import directory: //'
                ),
                'required' => true,
                'class' => 'validate-regex-pattern',
                'after_element_html' => $this->getRegexValidatorJs()
            ]
        );
        $fieldset->addField(
            'archive_path',
            'text',
            [
                'label' => __('Archive Directory'),
                'name' => 'archive_path',
                'note' => __(
                    'If you want to move the imported file(s) to another directory after they have been processed, please enter the path here. Use an absolute path or specify a path relative to the Magento root directory by putting a dot at the beginning. Example to move to the var/import/archive/ directory located in the root directory of Magento: ./var/import/archive/  Example to move to an absolute directory: /var/www/test/archive/ would move to the absolute path /var/www/test/archive/ (and not located in the Magento installation) This directory has to exist. Leave empty if you don\'t want to archive the import files.'
                ),
                'required' => false,
            ]
        );
        $fieldset->addField(
            'delete_imported_files',
            'select',
            [
                'label' => __('Delete imported files'),
                'name' => 'delete_imported_files',
                'values' => $this->yesNo->toOptionArray(),
                'note' => __(
                    'Set this to "Yes" if you want to delete the imported file from the local directory after it has been processed. You can\'t delete and archive at the same time, so choose either this option or the archive option above.'
                )
            ]
        );
    }

    protected function getRegexValidatorJs()
    {
        $errorMsg = __('This is no valid regular expression. It needs to begin and end with slashes: /sample/');
        $js = <<<EOT
    <script>
    require(['jquery', 'mage/backend/validation'], function ($) {
        jQuery.validator.addMethod('validate-regex-pattern', function(v, e) {
             if (v == "") {
                return true;
             }
             return RegExp("^\/(.*)\/","gi").test(v);
        }, '{$errorMsg}');
    });
    </script>
EOT;
        return $js;
    }
}