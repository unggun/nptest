<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Controller/Adminhtml/Tools/ImportSettings.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Controller\Adminhtml\Tools;

class ImportSettings extends \Xtento\OrderImport\Controller\Adminhtml\Tools
{
    /**
     * @var \Xtento\OrderImport\Helper\Tools
     */
    protected $toolsHelper;

    /**
     * ExportSettings constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Xtento\OrderImport\Helper\Module $moduleHelper
     * @param \Xtento\XtCore\Helper\Cron $cronHelper
     * @param \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Xtento\OrderImport\Model\ProfileFactory $profileFactory
     * @param \Xtento\OrderImport\Model\SourceFactory $sourceFactory
     * @param \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData
     * @param \Xtento\XtCore\Helper\Utils $utilsHelper
     * @param \Xtento\OrderImport\Helper\Tools $toolsHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Xtento\OrderImport\Helper\Module $moduleHelper,
        \Xtento\XtCore\Helper\Cron $cronHelper,
        \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Xtento\OrderImport\Model\ProfileFactory $profileFactory,
        \Xtento\OrderImport\Model\SourceFactory $sourceFactory,
        \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData,
        \Xtento\XtCore\Helper\Utils $utilsHelper,
        \Xtento\OrderImport\Helper\Tools $toolsHelper
    ) {
        parent::__construct($context, $moduleHelper, $cronHelper, $profileCollectionFactory, $scopeConfig, $profileFactory, $sourceFactory, $requestData, $utilsHelper);
        $this->toolsHelper = $toolsHelper;
    }

    /**
     * Import action
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Raw
     * @throws \Exception
     */
    public function execute()
    {
        // Check for uploaded file
        $settingsFile = $this->_request->getFiles('settings_file');
        if (!isset($settingsFile['tmp_name']) || empty($settingsFile['tmp_name'])) {
            $this->messageManager->addErrorMessage(__('No settings file has been uploaded.'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        $uploadedFile = $settingsFile['tmp_name'];
        // Check if data should be updated or added
        $updateByName = false;
        if ($this->getRequest()->getPost('update_by_name', false) == 'on') {
            $updateByName = true;
        }
        // Counters
        $addedCounter = ['profiles' => 0, 'sources' => 0];
        $updatedCounter = ['profiles' => 0, 'sources' => 0];
        $errorMessage = "";
        // Load and decode JSON settings
        $jsonData = file_get_contents($uploadedFile);
        if (!$this->toolsHelper->importSettingsFromJson($jsonData, $addedCounter, $updatedCounter, $updateByName, $errorMessage)) {
            $this->messageManager->addErrorMessage($errorMessage);
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        // Done
        $this->messageManager->addSuccessMessage(
            __(
                '%1 profiles have been added, %2 profiles have been updated, %3 sources have been added, %4 sources have been updated.',
                $addedCounter['profiles'],
                $updatedCounter['profiles'],
                $addedCounter['sources'],
                $updatedCounter['sources']
            )
        );
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');
        return $resultRedirect;
    }
}