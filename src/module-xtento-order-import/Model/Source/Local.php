<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-08-30T09:55:38+00:00
 * File:          app/code/Xtento/OrderImport/Model/Source/Local.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Source;

use Magento\Framework\DataObject;
use Xtento\OrderImport\Model\Log;

class Local extends AbstractClass
{
    public function testConnection()
    {
        $importDirectory = $this->fixBasePath($this->getSource()->getPath());
        $archiveDirectory = $this->fixBasePath($this->getSource()->getArchivePath());
        $testResult = new DataObject();

        // Check for forbidden folders
        $forbiddenFolders = [
            $this->filesystem->getDirectoryWrite(
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            )->getAbsolutePath()
        ];
        foreach ($forbiddenFolders as $forbiddenFolder) {
            if (realpath($importDirectory) == $forbiddenFolder) {
                return $testResult->setSuccess(false)->setMessage(
                    __(
                        'It is not allowed to load import files from the directory you have specified. Please change the local import directory to be located in the ./var/ folder for example. Do not use the Magento root directory for example.'
                    )
                );
            }
            if (!empty($archiveDirectory) && realpath($archiveDirectory) == $forbiddenFolder) {
                return $testResult->setSuccess(false)->setMessage(
                    __(
                        'It is not allowed to move archived files into the directory you have specified. Please change the archive directory to be located in the ./var/ folder for example. Do not use the Magento root directory for example.'
                    )
                );
            }
        }

        if (!is_dir($importDirectory) && !preg_match('/%importid%/', $importDirectory)) {
            return $testResult->setSuccess(false)->setMessage(
                __(
                    'The specified local import directory does not exist. Please create this directory or adjust the path. Could not load files from: %1',
                    $importDirectory
                )
            );
        }

        try {
            $this->connection = opendir($importDirectory);
            if (!$this->connection || !is_readable($importDirectory)) {
                return $testResult->setSuccess(false)->setMessage(
                    __(
                        'Could not open local import directory for reading. Please make sure that we have rights to read in the directory: %1',
                        $importDirectory
                    )
                );
            }
        } catch (\Exception $e) {
            return $testResult->setSuccess(false)->setMessage(
                __(
                    'Could not open local import directory for reading. Please make sure that we have rights to read in the directory: %1',
                    $importDirectory
                )
            );
        }

        $testResult->setSuccess(true)->setMessage(
            __('Local directory exists and is readable. Connection tested successfully.')
        );
        $this->getSource()->setLastResult($testResult->getSuccess())->setLastResultMessage(
            $testResult->getMessage()
        )->save();
        return $testResult;
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

        $importDirectory = $this->fixBasePath($this->getSource()->getPath());

        while (false !== ($filename = readdir($this->connection))) {
            if ($filename != "." && $filename != ".." && !is_dir($importDirectory . DIRECTORY_SEPARATOR . $filename)) {
                if (!preg_match($this->getSource()->getFilenamePattern(), $filename) && !preg_match(
                        '/\.chunk\./',
                        $filename
                    )
                ) {
                    continue;
                }
                $fileHandle = fopen($importDirectory . DIRECTORY_SEPARATOR . $filename, "r");
                if ($fileHandle) {
                    $buffer = '';
                    while (!feof($fileHandle)) {
                        $buffer .= fgets($fileHandle, 4096);
                    }
                    if (!empty($buffer)) {
                        $filesToProcess[] = [
                            'source_id' => $this->getSource()->getId(),
                            'path' => $importDirectory,
                            'filename' => $filename,
                            'data' => $buffer
                        ];
                    } else {
                        $this->archiveFiles(
                            [
                                [
                                    'source_id' => $this->getSource()->getId(),
                                    'path' => $importDirectory,
                                    'filename' => $filename
                                ]
                            ]
                        );
                    }
                } else {
                    $logEntry->setResult(Log::RESULT_WARNING);
                    $logEntry->addResultMessage(
                        __(
                            'Source "%1" (ID: %2): Could not open and read the file "%3" in the import directory.',
                            $this->getSource()->getName(),
                            $this->getSource()->getId(),
                            $testResult->getMessage(),
                            $filename
                        )
                    );
                    return false;
                }
            }
        }

        return $filesToProcess;
    }

    public function archiveFiles($filesToProcess, $forceDelete = false)
    {
        $logEntry = $this->_registry->registry('orderimport_log');
        $archiveDirectory = $this->fixBasePath($this->getSource()->getArchivePath());

        if ($forceDelete) {
            foreach ($filesToProcess as $file) {
                if ($file['source_id'] !== $this->getSource()->getId()) {
                    continue;
                }
                $unlinkResult = false;
                try {
                    $unlinkResult = unlink($file['path'] . $file['filename']);
                } catch (\Exception $e) {}
                if (!$unlinkResult) {
                    $logEntry->setResult(Log::RESULT_WARNING);
                    $logEntry->addResultMessage(
                        __(
                            'Source "%1" (ID: %2): Could not delete file "%3%4" from the local import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.',
                            $this->getSource()->getName(),
                            $this->getSource()->getId(),
                            $file['path'],
                            $file['filename']
                        )
                    );
                }
            }
        } else {
            if ($archiveDirectory !== "") {
                if (!is_dir($archiveDirectory)) {
                    $logEntry->setResult(Log::RESULT_WARNING);
                    $logEntry->addResultMessage(
                        __(
                            'Source "%1" (ID: %2): Archive directory does not exist. Please make sure the directory exists and that we have rights to read/write in the directory. Could not archive files.',
                            $this->getSource()->getName(),
                            $this->getSource()->getId()
                        )
                    );
                } else {
                    foreach ($filesToProcess as $file) {
                        if ($file['source_id'] !== $this->getSource()->getId()) {
                            continue;
                        }
                        $renameResult = false;
                        try {
                            $renameResult = rename($file['path'] . $file['filename'], $archiveDirectory . $file['filename']);
                        } catch (\Exception $e) {}
                        if (!$renameResult) {
                            $logEntry->setResult(Log::RESULT_WARNING);
                            $logEntry->addResultMessage(
                                __(
                                    'Source "%1" (ID: %2): Could not move file "%3%4" to the local archive directory located at "%5". Please make sure the directory exists and that we have rights to read/write in the directory.',
                                    $this->getSource()->getName(),
                                    $this->getSource()->getId(),
                                    $file['path'],
                                    $file['filename'],
                                    $archiveDirectory
                                )
                            );
                        }
                    }
                }
            } else {
                if ($this->getSource()->getDeleteImportedFiles() == true) {
                    foreach ($filesToProcess as $file) {
                        if ($file['source_id'] !== $this->getSource()->getId()) {
                            continue;
                        }
                        $unlinkResult = false;
                        try {
                            $unlinkResult = unlink($file['path'] . $file['filename']);
                        } catch (\Exception $e) {}
                        if (!$unlinkResult) {
                            $logEntry->setResult(Log::RESULT_WARNING);
                            $logEntry->addResultMessage(
                                __(
                                    'Source "%1" (ID: %2): Could not delete file "%3%4" from the local import directory after processing it. Please make sure the directory exists and that we have rights to read/write in the directory.',
                                    $this->getSource()->getName(),
                                    $this->getSource()->getId(),
                                    $file['path'],
                                    $file['filename']
                                )
                            );
                        }
                    }
                }
            }
        }
    }

    protected function fixBasePath($originalPath)
    {
        /*
        * Let's try to fix the import directory and replace the dot with the actual Magento root directory.
        * Why? Because if the cronjob is executed using the PHP binary a different working directory (when using a dot (.) in a directory path) could be used.
        * But Magento is able to return the right base path, so let's use it instead of the dot.
        */
        $originalPath = str_replace('/', DIRECTORY_SEPARATOR, $originalPath);
        if (substr($originalPath, 0, 2) == '.' . DIRECTORY_SEPARATOR) {
            return rtrim($this->filesystem->getDirectoryRead(
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            )->getAbsolutePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . substr($originalPath, 2);
        }
        return $originalPath;
    }
}