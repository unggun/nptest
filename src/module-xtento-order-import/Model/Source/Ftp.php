<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-06-24T18:57:06+00:00
 * File:          app/code/Xtento/OrderImport/Model/Source/Ftp.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Source;

use Magento\Framework\DataObject;
use Xtento\OrderImport\Model\Log;

class Ftp extends AbstractClass
{
    const TYPE_FTP = 'ftp';
    const TYPE_FTPS = 'ftps';

    /*
     * Download files from a FTP server
     */
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

        $this->connection = false;
        $warning = '';
        if ($this->getSource()->getFtpType() == self::TYPE_FTPS) {
            if (function_exists('ftp_ssl_connect')) {
                try {
                    $this->connection = ftp_ssl_connect(
                        $this->getSource()->getHostname(),
                        $this->getSource()->getPort(),
                        $this->getSource()->getTimeout()
                    );
                } catch (\Exception $e) {
                    $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                }
            } else {
                $this->getTestResult()->setSuccess(false)->setMessage(
                    __('No FTP-SSL functions found. Please compile PHP with SSL support.')
                );
                return false;
            }
        } else {
            if (function_exists('ftp_connect')) {
                try {
                    $this->connection = ftp_connect(
                        $this->getSource()->getHostname(),
                        $this->getSource()->getPort(),
                        $this->getSource()->getTimeout()
                    );
                } catch (\Exception $e) {
                    $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                }
            } else {
                $this->getTestResult()->setSuccess(false)->setMessage(
                    __('No FTP functions found. Please compile PHP with FTP support.')
                );
                return false;
            }
        }

        if (!$this->connection) {
            $this->getTestResult()->setSuccess(false)->setMessage(
                __(
                    'Could not connect to FTP server. Please make sure that there is no firewall blocking the outgoing connection to the FTP server and that the timeout is set to a high enough value. If this error keeps occurring, please get in touch with your server hoster / server administrator AND with the server hoster / server administrator of the remote FTP server. A firewall is probably blocking ingoing/outgoing FTP connections. %1', $warning
                )
            );
            return false;
        }

        $warning = '';
        $loginResult = false;
        try {
            $loginResult = ftp_login($this->connection, $this->getSource()->getUsername(), $this->encryptor->decrypt($this->getSource()->getPassword()));
        } catch (\Exception $e) {
            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
        }
        if (!$loginResult) {
            $this->getTestResult()->setSuccess(false)->setMessage(__('Could not log into FTP server. Wrong username or password. %1', $warning));
            return false;
        }

        if ($this->getSource()->getFtpIgnorepasvaddress()) {
            ftp_set_option($this->connection, FTP_USEPASVADDRESS, false);
        }

        if ($this->getSource()->getFtpPasv()) {
            // Enable passive mode
            try {
                ftp_pasv($this->connection, true);
            } catch (\Exception $e) {}
            # if (...) {$this->getTestResult()->setSuccess(false)->setMessage(__('Could not enable passive mode for FTP connection.'));
            #$this->getSource()->setLastResult($this->getTestResult()->getSuccess())->setLastResultMessage($this->getTestResult()->getMessage())->save();
            #return false;}
        }

        $warning = '';
        $chdirResult = false;
        try {
            $chdirResult = ftp_chdir($this->connection, $this->getSource()->getPath());
        } catch (\Exception $e) {
            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
        }
        if (!$chdirResult) {
            $this->getTestResult()->setSuccess(false)->setMessage(__('Could not change directory on FTP server to import directory. Please make sure the directory exists (base path must be exactly the same) and that we have rights to read in the directory. %1', $warning));
            return false;
        }

        $this->getTestResult()->setSuccess(true)->setMessage(__('Connection with FTP server tested successfully.'));
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

        $filelist = false;
        $warning = '';
        try {
            $filelist = ftp_nlist($this->connection, '');
        } catch (\Exception $e) {
            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
        }
        /* Alternative code for some broken FTP servers: */
        /*
        $filelist = ftp_rawlist($this->connection, "");
        $results = [];
        foreach ($filelist as $line) {
            $name = array_pop(explode(" ", $line));
            if ($name == '.' || $name == '..') continue;
            $results[] = $name;
        }
        $filelist = $results;
        */
        if ($filelist === false || !is_array($filelist)) {
            $logEntry->setResult(Log::RESULT_WARNING);
            $logEntry->addResultMessage(
                __(
                    'Source "%1" (ID: %2): Could not get file listing for import directory. You should try enabling Passive Mode in the modules FTP configuration. %4',
                    $this->getSource()->getName(),
                    $this->getSource()->getId(),
                    $warning
                )
            );
            return false;
        }
        foreach ($filelist as $filename) {
            if (!preg_match($this->getSource()->getFilenamePattern(), $filename)) {
                continue;
            }
            try {
                if (ftp_chdir($this->connection, $filename)) {
                    // This is a directory.. do not try to download it.
                    ftp_chdir($this->connection, '..');
                    continue;
                }
            } catch (\Exception $e) {}
            $tempHandle = fopen('php://temp', 'r+');
            $warning = '';
            $ftpGetResult = false;
            try {
                $ftpGetResult = ftp_fget($this->connection, $tempHandle, $filename, FTP_BINARY, 0);
            } catch (\Exception $e) {
                $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
            }
            if ($ftpGetResult) {
                rewind($tempHandle);
                $buffer = '';
                while (!feof($tempHandle)) {
                    $buffer .= fgets($tempHandle, 4096);
                }
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
                        'Source "%1" (ID: %2): Could not download file "%3" from FTP server. Please make sure we\'ve got rights to read the file. You can also try enabling Passive Mode in the configuration section of the extension. %4',
                        $this->getSource()->getName(),
                        $this->getSource()->getId(),
                        $filename,
                        $warning
                    )
                );
            }
        }

        // Close FTP connection
        ftp_close($this->connection);

        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false, $chDir = true, $closeConnection = true)
    {
        $logEntry = $this->_registry->registry('orderimport_log');

        // Reconnect to archive files
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

        if ($this->connection) {
            if ($forceDelete) {
                foreach ($filesToProcess as $file) {
                    if ($file['source_id'] !== $this->getSource()->getId()) {
                        continue;
                    }
                    $deleteResult = false;
                    $warning = '';
                    try {
                        $deleteResult = ftp_delete($this->connection, $file['path'] . '/' . $file['filename']);
                    } catch (\Exception $e) {
                        $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                    }
                    if (!$deleteResult) {
                        $logEntry->setResult(Log::RESULT_WARNING);
                        $logEntry->addResultMessage(
                            __(
                                'Source "%1" (ID: %2): Could not delete file "%3%4" from the FTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory. Also, make sure the import path is an absolute path, i.e. that it begins with a slash (/). %5',
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
                        $chdirResult = false;
                        $warning = '';
                        try {
                            $chdirResult = ftp_chdir($this->connection, $this->getSource()->getArchivePath());
                        } catch (\Exception $e) {
                            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                        }
                        if (!$chdirResult) {
                            $logEntry->setResult(Log::RESULT_WARNING);
                            $logEntry->addResultMessage(
                                __(
                                    'Source "%1" (ID: %2): Could not change directory on FTP server to archive directory. Please make sure the directory exists and that we have rights to read/write in the directory. Also, make sure the archive path is an absolute path, i.e. that it begins with a slash (/). %5',
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
                        $renameResult = false;
                        $warning = '';
                        try {
                            $renameResult = ftp_rename($this->connection, $file['path'] . '/' . $file['filename'], $file['filename']);
                        } catch (\Exception $e) {
                            $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                        }
                        if (!$renameResult) {
                            $logEntry->setResult(Log::RESULT_WARNING);
                            $logEntry->addResultMessage(
                                __(
                                    'Source "%1" (ID: %2): Could not move file "%3%4" to the FTP archive directory located at "%5". Please make sure the directory exists and that we have rights to read/write in the directory. Also, make sure the archive path is an absolute path, i.e. that it begins with a slash (/). %6',
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
                            $deleteResult = false;
                            $warning = '';
                            try {
                                $deleteResult = ftp_delete($this->connection, $file['path'] . '/' . $file['filename']);
                            } catch (\Exception $e) {
                                $warning = '(' . __('Detailed Error') . ': ' . substr($e->getMessage(), 0, strrpos($e->getMessage(), ' in ')) . ')';
                            }
                            if (!$deleteResult) {
                                $logEntry->setResult(Log::RESULT_WARNING);
                                $logEntry->addResultMessage(
                                    __(
                                        'Source "%1" (ID: %2): Could not delete file "%3%4" from the FTP import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory. Also, make sure the import path is an absolute path, i.e. that it begins with a slash (/). %5',
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
            if ($closeConnection) {
                try {
                    ftp_close($this->connection);
                } catch (\Exception $e) {}
            }
        }
    }
}