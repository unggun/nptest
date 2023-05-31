<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-19T15:03:57+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Iterator/RowIterator.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Iterator;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Xtento\OrderImport\Model\Log;
use Xtento\OrderImport\Model\Processor\Mapping\ActionFactory;

class RowIterator extends AbstractIterator
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ActionFactory
     */
    protected $mappingActionFactory;

    /**
     * Order constructor.
     *
     * @param Registry $frameworkRegistry
     * @param ObjectManagerInterface $objectManager
     * @param ActionFactory $mappingActionFactory
     * @param array $data
     */
    public function __construct(
        Registry $frameworkRegistry,
        ObjectManagerInterface $objectManager,
        ActionFactory $mappingActionFactory,
        array $data = []
    ) {
        $this->registry = $frameworkRegistry;
        $this->objectManager = $objectManager;
        $this->mappingActionFactory = $mappingActionFactory;

        parent::__construct($data);
    }

    public function processUpdates($updatesInFilesToProcess)
    {
        $logEntry = $this->registry->registry('orderimport_log');
        $profileConfiguration = $this->getProfile()->getConfiguration();

        $totalRecordCount = 0;
        $updatedRecordCount = 0;

        $importModel = $this->objectManager->create(
            'Xtento\OrderImport\Model\Import\Entity\\' . ucfirst($this->getProfile()->getEntity())
        );
        $importModel->setImportType($this->getImportType());
        $importModel->setTestMode($this->getTestMode());
        $importModel->setProfile($this->getProfile());

        // Get actions to apply
        $actionMapping = $this->mappingActionFactory->create();
        $actionMapping->setMappingData(isset($profileConfiguration['action']) ? $profileConfiguration['action'] : []);
        $importModel->setActionFields($actionMapping->getMappingFields());
        $importModel->setActions($actionMapping->getMapping());

        if (!$importModel->prepareImport($updatesInFilesToProcess)) {
            $logEntry->setResult(Log::RESULT_WARNING);
            $logEntry->addResultMessage(
                __(
                    "Files have been parsed, however, the prepareImport function complains that there were problems preparing the import data. Stopping import. Make sure your import processor is set up right."
                )
            );
            return false; // No updates to import.
        }

        foreach ($updatesInFilesToProcess as $updateFile) {
            $path = (isset($updateFile['FILE_INFORMATION']['path'])) ? $updateFile['FILE_INFORMATION']['path'] : '';
            $filename = $updateFile['FILE_INFORMATION']['filename'];
            $sourceId = $updateFile['FILE_INFORMATION']['source_id'];

            #ini_set('xdebug.var_display_max_depth', 10);
            #Zend_Debug::dump($updateFile);
            #die();
            $updatesToProcess = $updateFile['ROWS'];

            foreach ($updatesToProcess as $rowIdentifier => $updateData) {
                $totalRecordCount++;
                try {
                    if (empty($rowIdentifier)) {
                        continue;
                    }
                    if (isset($updateData['SKIP_FLAG']) && $updateData['SKIP_FLAG'] === true) {
                        $logEntry->addDebugMessage(
                            __(
                                "Row with identifier '%1' was skipped because of 'skip' field configuration XML set up in profile.",
                                str_replace('_SKIP', '', $rowIdentifier)
                            )
                        );
                        continue;
                    }

                    $importResult = $importModel->process($rowIdentifier, $updateData);

                    if (!$importResult || isset($importResult['error'])) {
                        $logEntry->addDebugMessage(
                            __("Notice: %1 | File '%2'", $importResult['error'], $path . $filename)
                        );
                        continue;
                    } else {
                        if (isset($importResult['changed']) && $importResult['changed'] && stristr($importResult['debug'], 'failed validation') === false) {
                            $updatedRecordCount++;
                        }
                        if (isset($importResult['debug'])) {
                            $logEntry->addDebugMessage(
                                sprintf("%s", $importResult['debug'])
                            ); // | File '" . $path . $filename . "'", $importResult['debug']));
                        }
                    }
                } catch (\Exception $e) {
                    // Don't break execution, but log the error.
                    $logEntry->addDebugMessage(
                        __("Exception catched for row with ID '%1' specified in '%2' from source ID '%3':\n%4",
                           $rowIdentifier,
                           $path . $filename,
                           $sourceId,
                           $e->getMessage() . "\n" . $e->getTraceAsString()
                        )
                    );
                    continue;
                }
            }
        }

        $importModel->afterRun();

        $importResult = ['total_record_count' => $totalRecordCount, 'updated_record_count' => $updatedRecordCount];
        return $importResult;
    }
}