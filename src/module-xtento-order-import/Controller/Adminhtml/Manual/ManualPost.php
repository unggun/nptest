<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:11:23+00:00
 * File:          app/code/Xtento/OrderImport/Controller/Adminhtml/Manual/ManualPost.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Controller\Adminhtml\Manual;

use Magento\Framework\App\Filesystem\DirectoryList;

class ManualPost extends \Xtento\OrderImport\Controller\Adminhtml\Manual
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Xtento\OrderImport\Model\ImportFactory
     */
    protected $importFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $logDir;

    /**
     * ManualPost constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Xtento\OrderImport\Helper\Module $moduleHelper
     * @param \Xtento\XtCore\Helper\Cron $cronHelper
     * @param \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Xtento\OrderImport\Model\ProfileFactory $profileFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Xtento\OrderImport\Model\ImportFactory $importFactory
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Xtento\OrderImport\Helper\Module $moduleHelper,
        \Xtento\XtCore\Helper\Cron $cronHelper,
        \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Xtento\OrderImport\Model\ProfileFactory $profileFactory,
        \Magento\Framework\Registry $registry,
        \Xtento\OrderImport\Model\ImportFactory $importFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct(
            $context,
            $moduleHelper,
            $cronHelper,
            $profileCollectionFactory,
            $scopeConfig,
            $profileFactory
        );
        $this->registry = $registry;
        $this->importFactory = $importFactory;
        $this->logDir = $filesystem->getDirectoryWrite(DirectoryList::LOG);
    }

    /**
     * Import action
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Raw
     * @throws \Exception
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getPost('profile_id');
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('No profile selected or this profile does not exist anymore.'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('xtento_orderimport/manual/index');
            return $resultRedirect;
        }
        $redirectParameters = ['profile_id' => $profile->getId()];
        // Import
        try {
            $beginTime = time();
            $importModel = $this->importFactory->create()->setProfile($profile);
            if ($this->getRequest()->getPost('test_mode') !== null) {
                $importModel->setTestMode(true);
                $redirectParameters['test'] = 1;
            }
            if ($this->getRequest()->getPost('debug_mode') !== null) {
                $importModel->setDebugMode(true);
                $redirectParameters['debug'] = 1;
            }
            // Was a file uploaded manually?
            $uploadedFile = $this->_request->getFiles('manual_file_upload');
            if (is_array($uploadedFile) && isset($uploadedFile['tmp_name']) && file_exists($uploadedFile['tmp_name'])) {
                $tmpFile = $uploadedFile['tmp_name'];
                $filename = basename($uploadedFile['name']);
                $uploadedFile = ['source_id' => '0', 'filename' => $filename, 'data' => file_get_contents($tmpFile)];
            } else {
                $uploadedFile = false;
            }
            // Start import
            $importResult = $importModel->manualImport($uploadedFile);
            if (!$importResult) {
                $this->messageManager->addWarning(__('There was an error processing this import.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(
                    \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
                );
                $resultRedirect->setPath('xtento_orderimport/manual/index', $redirectParameters);
                return $resultRedirect;
            }
            $endTime = time();
            if ($importModel->getTestMode()) {
                $successMessage = __(
                    "%1 of %2 records WOULD have been imported if this wasn't the test mode.",
                    $importResult['updated_record_count'],
                    $importResult['total_record_count']
                );
            } else {
                $successMessage = __(
                    '%1 of %2 records have been imported in %3 seconds. If some records haven\'t been imported, they probably simply didn\'t change and didn\'t need to be updated.',
                    $importResult['updated_record_count'],
                    $importResult['total_record_count'],
                    ($endTime - $beginTime)
                );
            }
            if ($importModel->getDebugMode()) {
                $this->registry->registry('orderimport_log')->addDebugMessage($successMessage);
                $this->setDebugMessages();
            }
            $this->messageManager->addSuccess($successMessage);
            if ($this->registry->registry('orderimport_log')->getResult(
                ) !== \Xtento\OrderImport\Model\Log::RESULT_SUCCESSFUL
            ) {
                $this->messageManager->addError(
                    __(nl2br($this->registry->registry('orderimport_log')->getResultMessage()))
                );
            }
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(
                \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
            );
            $resultRedirect->setPath('xtento_orderimport/manual/index', $redirectParameters);
            return $resultRedirect;
        } catch (\Exception $e) {
            if (isset($importModel) && $importModel->getDebugMode()) {
                $this->registry->registry('orderimport_log')->addDebugMessage(
                    __('Error: %1', nl2br($e->getMessage()))
                );
                $this->setDebugMessages();
            }
            $this->messageManager->addError(__('%1', nl2br($e->getMessage())));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('xtento_orderimport/manual/index', $redirectParameters);
            return $resultRedirect;
        }
    }

    protected function setDebugMessages()
    {
        $maxLen = 900000;
        $debugMessages = $this->registry->registry('orderimport_log')->getDebugMessages();
        if (strlen($debugMessages) > $maxLen) {
            $logFilename = 'xtento_orderimport_' . uniqid() . '.log';
            $this->logDir->writeFile($logFilename, str_replace("\n", "\r\n", $debugMessages));
            $debugMessages = substr(
                    $debugMessages,
                    0,
                    $maxLen
                ) . __("...\n\nThe debug messages are too long to be shown here. The full debug message log was saved in the ./var/log/%1 file.", $logFilename);
        }
        $this->_session->setData('xtento_orderimport_debug_log', $debugMessages);
        return $this;
    }
}