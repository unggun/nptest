<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-06-24T18:55:59+00:00
 * File:          app/code/Xtento/OrderImport/Model/Source/Sftp.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Source;

use Magento\Framework\DataObject;
use Xtento\OrderImport\Model\Log;

class Sftp extends AbstractClass
{
    public function testConnection()
    {
        $this->initConnection();
        return $this->getTestResult();
    }

    public function initConnection()
    {
        $this->setSource($this->sourceFactory->create()->load($this->getSource()->getId()));
        $testResult = new DataObject();
        $this->setTestResult($testResult);

        if (class_exists('\phpseclib\Net\SFTP')) { // Magento 2.1
            $this->connection = new \phpseclib\Net\SFTP($this->getSource()->getHostname(), $this->getSource()->getPort(), $this->getSource()->getTimeout());
        } elseif (class_exists('\Net_SFTP')) { // Magento 2.0
            $this->connection = new \Net_SFTP($this->getSource()->getHostname(), $this->getSource()->getPort(), $this->getSource()->getTimeout());
        } else {
            $this->getTestResult()->setSuccess(false)->setMessage(
                __('No SFTP functions found. The Net_SFTP class is missing.')
            );
            return false;
        }

        if (!$this->connection) {
            $this->getTestResult()->setSuccess(false)->setMessage(
                __(
                    'Could not connect to SFTP server. Please make sure that there is no firewall blocking the outgoing connection to the SFTP server and that the timeout is set to a high enough value. If this error keeps occurring, please get in touch with your server hoster / server administrator AND with the server hoster / server administrator of the remote SFTP server. A firewall is probably blocking ingoing/outgoing SFTP connections.'
                )
            );
            return false;
        }

        // Pub/Private key support - make sure to use adjust the loadKey function with the right key format: http://phpseclib.sourceforge.net/documentation/misc_crypt.html WARNING: Magentos version of phpseclib actually only implements CRYPT_RSA_PRIVATE_FORMAT_PKCS1 (and apparently PUTTY format since recently).
        /*$pk = new \phpseclib\Crypt\RSA();
        $pk->setPassword($this->encryptor->decrypt($this->getSource()->getPassword()));
        #$private_key = file_get_contents('/var/www/some/absolute/path/to/private.key'); // Or load the private key from a file
        $private_key = <<<KEY
-----BEGIN DSA PRIVATE KEY-----
....
-----END DSA PRIVATE KEY-----
KEY;

        if ($pk->loadKey($private_key) === false) {
            $this->getTestResult()->setSuccess(false)->setMessage(__('Could not load private key supplied. Make sure it is the PKCS1 format (openSSH) and that the supplied password is right.'));
            return false;
        }*/

        $warning = '';
        $loginResult = false;
        try {
            $loginResult = $this->connection->login($this->getSource()->getUsername(), $this->encryptor->decrypt($this->getSource()->getPassword()));
            //$loginResult = $this->connection->login($this->getSource()->getUsername(), $pk); // If using pubkey authentication, uncomment this and comment line above
        } catch (\Exception $e) {
            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
        }
        if (!$loginResult) {
            $this->getTestResult()->setSuccess(false)->setMessage(
                __(
                    'Connection to SFTP server failed (make sure no firewall is blocking the connection). This error could also be caused by a wrong login for the SFTP server. %1', $warning
                )
            );
            return false;
        }

        $warning = '';
        $chdirResult = false;
        try {
            $chdirResult = $this->connection->chdir($this->getSource()->getPath());
        } catch (\Exception $e) {
            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
        }
        if (!$chdirResult) {
            $this->getTestResult()->setSuccess(false)->setMessage(
                __(
                    'Could not change directory on SFTP server to import directory. Please make sure the directory exists and that we have rights to read in the directory. %1', $warning
                )
            );
            return false;
        }

        $this->getTestResult()->setSuccess(true)->setMessage(__('Connection with SFTP server tested successfully.'));
        $this->getSource()->setLastResult($this->getTestResult()->getSuccess())->setLastResultMessage(
            $this->getTestResult()->getMessage()
        )->save();
        return true;
    }

    public function loadFiles()
    {
        $filesToProcess = [];

        $logEntry = $this->_registry->registry('orderimport_log');
        // Test connection
        $testResult = $this->testConnection();
        if (!$testResult->getSuccess()) {
            $logEntry->setResult(Log::RESULT_WARNING);
            $logEntry->addResultMessage(
                __(
                    'Source "%1" (ID: %2): %3',
                    $this->getSource()->getName(),
                    $this->getSource()->getId(),
                    $testResult->getMessage()
                )
            );
            return false;
        }

        try {
            $filelist = $this->connection->rawlist();
        } catch (\Exception $e) {
            $logEntry->setResult(Log::RESULT_WARNING);
            $logEntry->addResultMessage(
                __(
                    'Source "%1" (ID: %2): Could not download file listing SFTP server. Error: %3',
                    $this->getSource()->getName(),
                    $this->getSource()->getId(),
                    substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in '))
                )
            );
            return $filesToProcess;
        }
        foreach ($filelist as $filename => $fileinfo) {
            if (!preg_match($this->getSource()->getFilenamePattern(), $filename)) {
                continue;
            }
            if (!isset($fileinfo['size'])) {
                continue; // This is a directory.
            }
            $fs_filename = $filename;
            $warning = '';
            $buffer = false;
            try {
                $buffer = $this->connection->get($fs_filename);
            } catch (\Exception $e) {
                $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
            }
            if ($buffer !== false) {
                if (!empty($buffer)) {
                    $filesToProcess[] = [
                        'source_id' => $this->getSource()->getId(),
                        'path' => $this->getSource()->getPath(),
                        'filename' => $filename,
                        'data' => $buffer
                    ];
                } else if (!$this->getSource()->getSkipEmptyFiles()) { // Only if skip empty files is disabled, then archive/delete
                    $this->archiveFiles(
                        [
                            [
                                'source_id' => $this->getSource()->getId(),
                                'path' => $this->getSource()->getPath(),
                                'filename' => $filename
                            ]
                        ],
                        false,
                        false
                    );
                } else {
                    // Empty file, don't do anything
                }
            } else {
                $logEntry->setResult(Log::RESULT_WARNING);
                $logEntry->addResultMessage(
                    __(
                        'Source "%1" (ID: %2): Could not download file "%3" from SFTP server. Please make sure we\'ve got rights to read the file. %4',
                        $this->getSource()->getName(),
                        $this->getSource()->getId(),
                        $filename,
                        $warning
                    )
                );
            }
        }

        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false, $chDir = true)
    {
        $logEntry = $this->_registry->registry('orderimport_log');

        if ($this->connection) {
            if ($forceDelete) {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    $warning = '';
                    $deleteResult = false;
                    try {
                        $deleteResult = $this->connection->delete($file['path'] . '/' . $file['filename']);
                    } catch (\Exception $e) {
                        $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                    }
                    if (!$deleteResult) {
                        $logEntry->setResult(Log::RESULT_WARNING);
                        $logEntry->addResultMessage(
                            __(
                                'Source "%1" (ID: %2): Could not delete file "%3%4" from the SFTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory. %5',
                                $this->getSource()->getName(),
                                $this->getSource()->getId(),
                                $file['path'],
                                $file['filename'],
                                $warning
                            )
                        );
                    }
                }
            } else {
                if ($this->getSource()->getArchivePath() !== "") {
                    if ($chDir) {
                        $warning = '';
                        $chdirResult = false;
                        try {
                            $chdirResult = $this->connection->chdir($this->getSource()->getArchivePath());
                        } catch (\Exception $e) {
                            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                        }
                        if (!$chdirResult) {
                            $logEntry->setResult(Log::RESULT_WARNING);
                            $logEntry->addResultMessage(
                                __(
                                    'Source "%1" (ID: %2): Could not change directory on SFTP server to archive directory. Please make sure the directory exists and that we have rights to read/write in the directory. %3',
                                    $this->getSource()->getName(),
                                    $this->getSource()->getId(),
                                    $warning
                                )
                            );
                            return false;
                        }
                    }
                    foreach ($filesToProcess as $file) {
                        if ($file['source_id'] !== $this->getSource()->getId()) {
                            continue;
                        }
                        $warning = '';
                        $renameResult = false;
                        try {
                            $renameResult = $this->connection->rename($file['path'] . '/' . $file['filename'], $this->getSource()->getArchivePath() . '/' . $file['filename']);
                        } catch (\Exception $e) {
                            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                        }
                        if (!$renameResult) {
                            $logEntry->setResult(Log::RESULT_WARNING);
                            $logEntry->addResultMessage(
                                __(
                                    'Source "%1" (ID: %2): Could not move file "%3%4" to the SFTP archive directory located at "%5". Please make sure the directory exists and that we have rights to read/write in the directory. %6',
                                    $this->getSource()->getName(),
                                    $this->getSource()->getId(),
                                    $file['path'],
                                    $file['filename'],
                                    $this->getSource()->getArchivePath(),
                                    $warning
                                )
                            );
                        }
                    }
                } else {
                    if ($this->getSource()->getDeleteImportedFiles() == true) {
                        foreach ($filesToProcess as $file) {
                            if ($file['source_id'] !== $this->getSource()->getId()) {
                                continue;
                            }
                            $warning = '';
                            $deleteResult = false;
                            try {
                                $deleteResult = $this->connection->delete($file['path'] . '/' . $file['filename']);
                            } catch (\Exception $e) {
                                $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                            }
                            if (!$deleteResult) {
                                $logEntry->setResult(Log::RESULT_WARNING);
                                $logEntry->addResultMessage(
                                    __(
                                        'Source "%1" (ID: %2): Could not delete file "%3%4" from the SFTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory. %5',
                                        $this->getSource()->getName(),
                                        $this->getSource()->getId(),
                                        $file['path'],
                                        $file['filename'],
                                        $warning
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}