<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-06-24T18:57:29+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Source/Edit/Tab/Type/Ftp.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Source\Edit\Tab\Type;

class Ftp extends AbstractType
{
    // FTP Configuration
    public function getFields(\Magento\Framework\Data\Form $form, $type = 'FTP')
    {
        $model = $this->registry->registry('orderimport_source');
        if ($type == 'FTP') {
            $fieldset = $form->addFieldset(
                'config_fieldset',
                [
                    'legend' => __('FTP Configuration'),
                ]
            );
        } else {
            // SFTP
            $fieldset = $form->addFieldset(
                'config_fieldset',
                [
                    'legend' => __('SFTP Configuration'),
                ]
            );
            $fieldset->addField(
                'sftp_note',
                'note',
                [
                    'text' => __(
                        '<strong>Important</strong>: Only SFTPv3 servers are supported. Please make sure the server you\'re trying to connect to is a SFTPv3 server.'
                    )
                ]
            );
        }

        $fieldset->addField(
            'hostname',
            'text',
            [
                'label' => __('IP or Hostname'),
                'name' => 'hostname',
                'required' => true,
            ]
        );
        if ($type == 'FTP') {
            $fieldset->addField(
                'ftp_type',
                'select',
                [
                    'label' => __('Server Type'),
                    'name' => 'ftp_type',
                    'options' => [
                        \Xtento\OrderImport\Model\Source\Ftp::TYPE_FTP => 'FTP',
                        \Xtento\OrderImport\Model\Source\Ftp::TYPE_FTPS => 'FTPS ("FTP SSL")',
                    ],
                    'note' => __(
                        'FTPS is only available if PHP has been compiled with OpenSSL support. Only some server versions are supported, support is limited by PHP.'
                    )
                ]
            );
        }
        $fieldset->addField(
            'port',
            'text',
            [
                'label' => __('Port'),
                'name' => 'port',
                'note' => __('Default Port: %1', ($type == 'FTP') ? 21 : 22),
                'class' => 'validate-number',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'username',
            'text',
            [
                'label' => __('Username'),
                'name' => 'username',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'new_password',
            'obscure',
            [
                'label' => __('Password'),
                'name' => 'new_password',
                'required' => true,
            ]
        );
        $model->setNewPassword(($model->getPassword()) ? '******' : '');
        $fieldset->addField(
            'timeout',
            'text',
            [
                'label' => __('Timeout'),
                'name' => 'timeout',
                'note' => __('Timeout in seconds after which the connection to the server fails'),
                'required' => true,
                'class' => 'validate-number'
            ]
        );
        if ($type == 'FTP') {
            $fieldset->addField(
                'ftp_pasv',
                'select',
                [
                    'label' => __('Enable Passive Mode'),
                    'name' => 'ftp_pasv',
                    'values' => $this->yesNo->toOptionArray(),
                    'note' => __(
                        'If your server is behind a firewall, or if the extension has problems uploading the imported files, please set this to "Yes".'
                    )
                ]
            );
            $fieldset->addField('ftp_ignorepasvaddress', 'select', [
                'label' => __('Passive Mode: Ignore IP returned by server'),
                'name' => 'ftp_ignorepasvaddress',
                'values' => $this->yesNo->toOptionArray(),
                'note' => __('Default value: No. If enabled, the (local) IP address returned by the FTP server will be ignored (useful for servers behind NAT) and instead the servers public IP address will be used. Can help with "Operation now in progress" errors or other directory listing/transfer issues.')
            ]
            );
        }
        $fieldset->addField(
            'path',
            'text',
            [
                'label' => __('Import Directory'),
                'name' => 'path',
                'note' => __(
                    'This is the absolute path to the directory on the server where files will be downloaded from. This directory has to exist on the FTP server.'
                ),
                'required' => true,
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
                    'If you want to move the imported file(s) to another directory after they have been processed, please enter the path here. This is the absolute path to the archive directory on the FTP server. This directory has to exist on the FTP server. Leave empty if you don\'t want to archive the import files.'
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
                    'Set this to "Yes" if you want to delete the imported file from the FTP server after it has been processed. You can\'t delete and archive at the same time, so choose either this option or the archive option above.'
                )
            ]
        );
        $fieldset->addField(
            'skip_empty_files',
            'select',
            [
                'label' => __('Skip empty files'),
                'name' => 'skip_empty_files',
                'values' => $this->yesNo->toOptionArray(),
                'note' => __(
                    'If enabled, empty files will not be processed/archived/deleted by this import source. This is a possible protection against processing files that are still being uploaded by a third party to the (S)FTP server and haven\'t finished uploading yet.'
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