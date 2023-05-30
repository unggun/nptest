<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-08-30T12:11:36+00:00
 * File:          app/code/Xtento/OrderImport/Helper/Tools.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Helper;

use Magento\Framework\ObjectManagerInterface;
use Xtento\XtCore\Helper\Utils;

class Tools extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Xtento\OrderImport\Model\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Xtento\OrderImport\Model\SourceFactory
     */
    protected $sourceFactory;

    /**
     * @var Utils
     */
    protected $utilsHelper;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Tools constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Xtento\OrderImport\Model\ProfileFactory $profileFactory
     * @param \Xtento\OrderImport\Model\SourceFactory $sourceFactory
     * @param Utils $utilsHelper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Xtento\OrderImport\Model\ProfileFactory $profileFactory,
        \Xtento\OrderImport\Model\SourceFactory $sourceFactory,
        Utils $utilsHelper,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
        $this->profileFactory = $profileFactory;
        $this->sourceFactory = $sourceFactory;
        $this->utilsHelper = $utilsHelper;
        $this->objectManager = $objectManager;
    }

    /**
     * @param $profileIds
     * @param $sourceIds
     *
     * @return string
     */
    public function exportSettingsAsJson($profileIds, $sourceIds)
    {
        $randIdPrefix = rand(100000, 999999);
        $exportData = [];
        $exportData['profiles'] = [];
        $exportData['sources'] = [];
        foreach ($profileIds as $profileId) {
            $profile = $this->profileFactory->create()->load($profileId);
            if ($profile->getId()) {
                $profile->unsetData('profile_id');
                $profileSourceIds = $profile->getData('source_ids');
                $newSourceIds = [];
                foreach (explode("&", $profileSourceIds) as $sourceId) {
                    if (is_numeric($sourceId)) {
                        $newSourceIds[] = substr($randIdPrefix . $sourceId, 0, 8);
                    }
                }
                $profile->setData('new_source_ids', implode("&", $newSourceIds));
                $exportData['profiles'][] = $profile->toArray();
            }
        }
        foreach ($sourceIds as $sourceId) {
            $source = $this->sourceFactory->create()->load($sourceId);
            if ($source->getId()) {
                $source->setData('new_source_id', substr($randIdPrefix . $sourceId, 0, 8));
                $source->unsetData('password');
                $exportData['sources'][] = $source->toArray();
            }
        }
        return \Zend_Json::encode($exportData);
    }

    /**
     * @param $jsonData
     * @param array $addedCounter
     * @param array $updatedCounter
     * @param bool $updateByName
     * @param string $errorMessage
     *
     * @return bool
     */
    public function importSettingsFromJson($jsonData, &$addedCounter = [], &$updatedCounter = [], $updateByName = true, &$errorMessage = "")
    {
        try {
            $settingsArray = \Zend_Json::decode($jsonData);
        } catch (\Exception $e) {
            $errorMessage = __('Import failed. Decoding of JSON import format failed.');
            return false;
        }
        // In Magento 1.x and 2.0/2.1 some fields were stored serialized. Thus, we need to convert them to JSON if importing into Magento 2.2+
        $serializedToJsonConverter = false;
        if (version_compare($this->utilsHelper->getMagentoVersion(), '2.2', '>=')) {
            $serializedToJsonConverter = $this->objectManager->create('\Xtento\OrderImport\Test\SerializedToJsonDataConverter');
        }
        // Remapped source IDs
        $remappedSourceIds = [];
        // Process sources
        if (isset($settingsArray['sources'])) {
            foreach ($settingsArray['sources'] as $sourceData) {
                if ($updateByName) {
                    $sourceCollection = $this->sourceFactory->create()->getCollection()
                        ->addFieldToFilter('type', $sourceData['type'])
                        ->addFieldToFilter('name', $sourceData['name']);
                    if ($sourceCollection->getSize() === 1) {
                        $remappedSourceIds[$sourceData['new_source_id']] = $sourceCollection->getFirstItem()->getId();
                        unset($sourceData['new_source_id']);
                        $this->sourceFactory->create()->setData($sourceData)->setId(
                            $sourceCollection->getFirstItem()->getId()
                        )->save();
                        $updatedCounter['sources']++;
                    } else {
                        $newSource = $this->sourceFactory->create()->setData($sourceData);
                        if (isset($sourceData['new_source_id'])) {
                            $newSource->setId($sourceData['new_source_id']);
                            unset($sourceData['new_source_id']);
                            $newSource->saveWithId();
                        } else {
                            unset($sourceData['new_source_id']);
                            $newSource->save();
                        }
                        $addedCounter['sources']++;
                    }
                } else {
                    $newSource = $this->sourceFactory->create()->setData($sourceData);
                    if (isset($sourceData['new_source_id'])) {
                        $newSource->setId($sourceData['new_source_id']);
                        unset($sourceData['new_source_id']);
                        $newSource->saveWithId();
                    } else {
                        unset($sourceData['new_source_id']);
                        $newSource->save();
                    }
                    $addedCounter['sources']++;
                }
            }
        }
        // Process profiles
        if (isset($settingsArray['profiles'])) {
            foreach ($settingsArray['profiles'] as $profileData) {
                if ($serializedToJsonConverter !== false) {
                    if (isset($profileData['conditions_serialized']))
                        $profileData['conditions_serialized'] = $serializedToJsonConverter->convert($profileData['conditions_serialized']);
                    if (isset($profileData['configuration']))
                        $profileData['configuration'] = $serializedToJsonConverter->convert($profileData['configuration']);
                }
                // If importing a settings file from Magento >=2.2 into <=2.1, we must make sure that the "_serialized" fields are indeed serialized and not JSON
                if (version_compare($this->utilsHelper->getMagentoVersion(), '2.2', '<')) {
                    $fieldsToCheck = ['conditions_serialized', 'configuration'];
                    foreach ($fieldsToCheck as $fieldToCheck) {
                        if (isset($profileData[$fieldToCheck])) {
                            try {
                                $jsonData = json_decode($profileData[$fieldToCheck], true);
                            } catch (\Exception $e) {
                                $jsonData = '';
                            }
                            if (json_last_error() == JSON_ERROR_NONE) {
                                // It's json, we need to serialize it for M2.0/2.1
                                $profileData[$fieldToCheck] = serialize($jsonData);
                            }
                        }
                    }
                }
                // Begin import
                if ($updateByName) {
                    $profileCollection = $this->profileFactory->create()->getCollection()
                        ->addFieldToFilter('entity', $profileData['entity'])
                        ->addFieldToFilter('name', $profileData['name']);
                    if (isset($profileData['new_source_ids'])) {
                        $newSourceIds = explode("&", $profileData['new_source_ids']);
                        $tempSourceIds = [];
                        foreach ($newSourceIds as $newSourceId) {
                            if (isset($remappedSourceIds[$newSourceId])) {
                                $newSourceId = $remappedSourceIds[$newSourceId];
                            }
                            $tempSourceIds[] = $newSourceId;
                        }
                        $profileData['source_ids'] = implode("&", $newSourceIds);
                        unset($profileData['new_source_ids']);
                    }
                    if ($profileCollection->getSize() === 1) {
                        $this->profileFactory->create()->setData($profileData)->setId($profileCollection->getFirstItem()->getId())->save();
                        $updatedCounter['profiles']++;
                    } else {
                        $this->profileFactory->create()->setData($profileData)->save();
                        $addedCounter['profiles']++;
                    }
                } else {
                    if (isset($profileData['new_source_ids'])) {
                        $profileData['source_ids'] = $profileData['new_source_ids'];
                        unset($profileData['new_source_ids']);
                    }
                    $this->profileFactory->create()->setData($profileData)->save();
                    $addedCounter['profiles']++;
                }
            }
        }
        return true;
    }
}
