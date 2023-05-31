<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-10-09T19:21:52+00:00
 * File:          app/code/Xtento/OrderImport/Model/Profile.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model;

class Profile extends \Magento\Rule\Model\AbstractModel
{
    /**
     * @var Import\Condition\CombineFactory
     */
    protected $combineFactory;

    /**
     * @var Import\Condition\ActionFactory
     */
    protected $actionFactory;

    /**
     * @var \Xtento\OrderImport\Helper\Module
     */
    protected $moduleHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var SourceFactory
     */
    protected $sourceFactory;

    /**
     * @var \Xtento\XtCore\Helper\Cron
     */
    protected $cronHelper;

    /**
     * Profile constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param Import\Condition\CombineFactory $combineFactory
     * @param Import\Condition\ActionFactory $actionFactory
     * @param \Xtento\OrderImport\Helper\Module $moduleHelper
     * @param \Xtento\XtCore\Helper\Cron $cronHelper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param SourceFactory $sourceFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Xtento\OrderImport\Model\Import\Condition\CombineFactory $combineFactory,
        \Xtento\OrderImport\Model\Import\Condition\ActionFactory $actionFactory,
        \Xtento\OrderImport\Helper\Module $moduleHelper,
        \Xtento\XtCore\Helper\Cron $cronHelper,
        \Magento\Framework\App\RequestInterface $request,
        \Xtento\OrderImport\Model\SourceFactory $sourceFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->combineFactory = $combineFactory;
        $this->actionFactory = $actionFactory;
        $this->moduleHelper = $moduleHelper;
        $this->cronHelper = $cronHelper;
        $this->request = $request;
        $this->sourceFactory = $sourceFactory;
        parent::__construct($context, $registry, $formFactory, $localeDate, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Xtento\OrderImport\Model\ResourceModel\Profile');
        $this->_collectionName = 'Xtento\OrderImport\Model\ResourceModel\Profile\Collection';
    }

    /**
     * @return \Magento\Rule\Model\Condition\Combine
     */
    public function getConditionsInstance()
    {
        $this->_registry->register('orderimport_profile', $this, true);
        return $this->combineFactory->create();
    }

    /**
     * @return \Magento\Rule\Model\Action\Collection
     */
    public function getActionsInstance()
    {
        return $this->actionFactory->create();
    }

    public function getSources()
    {
        $sourceIds = array_filter(explode("&", $this->getData('source_ids')));
        $sources = [];
        foreach ($sourceIds as $sourceId) {
            if (!is_numeric($sourceId)) {
                continue;
            }
            $source = $this->sourceFactory->create()->load($sourceId);
            if ($source->getId()) {
                $sources[] = $source;
            }
        }
        // Return sources
        return $sources;
    }

    public function beforeSave()
    {
        // Only call the "rule" model parents _beforeSave function if the profile is modified in the backend, as otherwise the "conditions" ("import filters") could be lost
        if ($this->request->getModuleName() == 'xtento_orderimport' && $this->request->getControllerName(
            ) == 'profile'
        ) {
            parent::beforeSave();
        } else {
            if (!$this->getId()) {
                $this->isObjectNew(true);
            }
        }
        return $this;
    }

    public function afterSave()
    {
        parent::afterSave();
        if ($this->request->getModuleName() == 'xtento_orderimport' && ($this->request->getControllerName() == 'profile' || $this->request->getControllerName() == 'tools')) {
            $this->updateCronjobs();
        }
        if ($this->_registry->registry('xtento_orderimport_update_cronjobs_after_profile_save') !== null) {
            // Can be registered by third party developers, so after they call ->save() on a profile, it will update the profiles cronjobs
            $this->updateCronjobs();
        }
        return $this;
    }

    public function beforeDelete()
    {
        // Remove existing cronjobs
        $this->cronHelper->removeCronjobsLike('orderimport_profile_' . $this->getId() . '_%', \Xtento\OrderImport\Cron\Import::CRON_GROUP);

        return parent::beforeDelete();
    }

    /**
     * Update database via cron helper
     */
    protected function updateCronjobs()
    {
        // Remove existing cronjobs
        $this->cronHelper->removeCronjobsLike('orderimport_profile_' . $this->getId() . '_%', \Xtento\OrderImport\Cron\Import::CRON_GROUP);

        if (!$this->getEnabled()) {
            return $this; // Profile not enabled
        }
        if (!$this->getCronjobEnabled()) {
            return $this; // Cronjob not enabled
        }

        $cronRunModel = 'Xtento\OrderImport\Cron\Import::execute';
        if ($this->getCronjobFrequency(
            ) == \Xtento\OrderImport\Model\System\Config\Source\Cron\Frequency::CRON_CUSTOM
            || ($this->getCronjobFrequency() == '' && $this->getCronjobCustomFrequency() !== '')
        ) {
            // Custom cron expression
            $cronFrequencies = $this->getCronjobCustomFrequency();
            if (empty($cronFrequencies)) {
                return $this;
            }
            $cronFrequencies = array_unique(explode(";", $cronFrequencies));
            $cronCounter = 0;
            foreach ($cronFrequencies as $cronFrequency) {
                $cronFrequency = trim($cronFrequency);
                if (empty($cronFrequency)) {
                    continue;
                }
                $cronCounter++;
                $cronIdentifier = 'orderimport_profile_' . $this->getId() . '_cron_' . $cronCounter;
                $this->cronHelper->addCronjob(
                    $cronIdentifier,
                    $cronFrequency,
                    $cronRunModel,
                    \Xtento\OrderImport\Cron\Import::CRON_GROUP
                );
            }
        } else {
            // No custom cron expression
            $cronFrequency = $this->getCronjobFrequency();
            if (empty($cronFrequency)) {
                return $this;
            }
            $cronIdentifier = 'orderimport_profile_' . $this->getId() . '_cron';
            $this->cronHelper->addCronjob(
                $cronIdentifier,
                $cronFrequency,
                $cronRunModel,
                \Xtento\OrderImport\Cron\Import::CRON_GROUP
            );
        }

        return $this;
    }

    public function saveLastExecutionNow()
    {
        $write = $this->getResource()->getConnection();
        $write->update(
            $this->getResource()->getMainTable(),
            ['last_execution' => (new \DateTime)->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)],
            ["`{$this->getResource()->getIdFieldName()}` = {$this->getId()}"]
        );
    }
}