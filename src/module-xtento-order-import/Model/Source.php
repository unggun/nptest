<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/Source.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model;

/**
 * Class Source
 * Source model containing information about "sources" where imported files are retrieved from
 * @package Xtento\OrderImport\Model
 */
class Source extends \Magento\Framework\Model\AbstractModel
{
    protected $sourceClass = false;

    /**
     * Source Types
     */
    const TYPE_LOCAL = 'local';
    const TYPE_FTP = 'ftp';
    const TYPE_SFTP = 'sftp';
    const TYPE_HTTP = 'http';
    const TYPE_HTTPDOWNLOAD = 'httpdownload';
    const TYPE_EMAIL = 'email';
    const TYPE_WEBSERVICE = 'webservice';
    const TYPE_CUSTOM = 'custom';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var SourceFactory
     */
    protected $sourceFactory;

    /**
     * @var ResourceModel\Profile\CollectionFactory
     */
    protected $profileCollectionFactory;

    /**
     * Source constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param SourceFactory $sourceFactory
     * @param ResourceModel\Profile\CollectionFactory $profileCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        SourceFactory $sourceFactory,
        \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->date = $date;
        $this->objectManager = $objectManager;
        $this->sourceFactory = $sourceFactory;
        $this->profileCollectionFactory = $profileCollectionFactory;
    }

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('Xtento\OrderImport\Model\ResourceModel\Source');
        $this->_collectionName = 'Xtento\OrderImport\Model\ResourceModel\Source\Collection';
    }

    /**
     * Return source types
     * @return array
     */
    public function getTypes()
    {
        $values = [];
        $values[self::TYPE_LOCAL] = __('Local Directory');
        $values[self::TYPE_FTP] = __('FTP Server');
        $values[self::TYPE_SFTP] = __('SFTP Server');
        $values[self::TYPE_HTTPDOWNLOAD] = __('HTTP URL Download');
        $values[self::TYPE_HTTP] = __('HTTP Server (Custom)');
        $values[self::TYPE_WEBSERVICE] = __('Webservice/API');
        $values[self::TYPE_CUSTOM] = __('Custom Class');
        return $values;
    }

    /**
     * Set last result message for this source
     *
     * @param $message
     *
     * @return $this
     */
    public function setLastResultMessage($message)
    {
        $this->setData(
            'last_result_message',
            date('c', $this->date->timestamp()) . ": " . $message
        );
        return $this;
    }

    /**
     * Retrieve files from import sources
     * @return array
     */
    public function loadFiles()
    {
        $this->sourceClass = $this->objectManager->create(
            '\Xtento\OrderImport\Model\Source\\' . ucfirst($this->getData('type'))
        );
        if ($this->sourceClass !== false) {
            $this->sourceClass->setSource($this);
            return $this->sourceClass->loadFiles();
        }
        return [];
    }

    /**
     * Archive processed files
     *
     * @param $filesToProcess
     *
     * @return mixed
     */
    public function archiveFiles($filesToProcess)
    {
        if ($this->sourceClass !== false) {
            return $this->sourceClass->archiveFiles($filesToProcess);
        }
    }

    /**
     * Retrieve profiles which are using this source.
     * @return array
     */
    public function getProfileUsage()
    {
        $profileUsage = [];
        $profileCollection = $this->profileCollectionFactory->create();
        $profileCollection->addFieldToFilter('source_ids', ['neq' => '']);
        $profileCollection->getSelect()->order('entity ASC');
        foreach ($profileCollection as $profile) {
            $sourceIds = explode("&", $profile->getData('source_ids'));
            if (in_array($this->getId(), $sourceIds)) {
                $profileUsage[] = $profile;
            }
        }
        return $profileUsage;
    }

    /**
     * Overwrite ID when importing sources.
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveWithId()
    {
        // First check if the ID we've set exists as a model right now.
        $realId = $this->getId();
        $idExists = $this->sourceFactory->create()->setId(null)->load($realId)->getId();

        // Perform the regular saving routine as if it's a new model
        if (!$idExists) {
            $this->setId(null);
        }
        $this->save();

        // Update the new model we created with the original ID.
        if (!$idExists) {
            $write = $this->getResource()->getConnection();
            $write->update(
                $this->getResource()->getMainTable(),
                [$this->getResource()->getIdFieldName() => $realId],
                ["`{$this->getResource()->getIdFieldName()}` = {$this->getId()}"]
            );
            #$write->commit();
        }

        return $this;
    }

    /**
     * Fix bad user input for specific configuration values when requested by the module
     * @return mixed|string
     */
    public function getHostname()
    {
        $hostname = $this->getData('hostname');
        $hostname = str_replace(['ftp://', 'http://'], '', $hostname);
        $hostname = trim($hostname);
        return $hostname;
    }

    /**
     * Fix bad user input for specific configuration values when requested by the module
     * @return mixed|string
     */
    public function getPort()
    {
        $port = $this->getData('port');
        $port = preg_replace('/[^0-9]/', '', $port);
        return $port;
    }

    /**
     * Fix bad user input for specific configuration values when requested by the module
     * @return mixed|string
     */
    public function getTimeout()
    {
        $timeout = $this->getData('timeout');
        $timeout = preg_replace('/[^0-9]/', '', $timeout);
        return $timeout;
    }
}