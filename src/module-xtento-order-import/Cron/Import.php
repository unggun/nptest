<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-04T14:59:42+00:00
 * File:          app/code/Xtento/OrderImport/Cron/Import.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Cron;

use Magento\Framework\Exception\LocalizedException;

class Import extends \Magento\Framework\Model\AbstractModel
{
    const CRON_GROUP = 'xtento_orderimport';

    /**
     * @var \Xtento\OrderImport\Helper\Module
     */
    protected $moduleHelper;

    /**
     * @var \Xtento\OrderImport\Model\ImportFactory
     */
    protected $importFactory;

    /**
     * @var \Xtento\OrderImport\Logger\Logger
     */
    protected $xtentoLogger;

    /**
     * @var \Xtento\OrderImport\Model\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Xtento\XtCore\Helper\Cron
     */
    protected $cronHelper;

    /**
     * Import constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Xtento\OrderImport\Helper\Module $moduleHelper
     * @param \Xtento\OrderImport\Model\ProfileFactory $profileFactory
     * @param \Xtento\OrderImport\Model\ImportFactory $importFactory
     * @param \Xtento\OrderImport\Logger\Logger $xtentoLogger
     * @param \Xtento\XtCore\Helper\Cron $cronHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Xtento\OrderImport\Helper\Module $moduleHelper,
        \Xtento\OrderImport\Model\ProfileFactory $profileFactory,
        \Xtento\OrderImport\Model\ImportFactory $importFactory,
        \Xtento\OrderImport\Logger\Logger $xtentoLogger,
        \Xtento\XtCore\Helper\Cron $cronHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->importFactory = $importFactory;
        $this->xtentoLogger = $xtentoLogger;
        $this->profileFactory = $profileFactory;
        $this->cronHelper = $cronHelper;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Run automatic import, dispatched by Magento cron scheduler
     *
     * @param $schedule
     */
    public function execute($schedule)
    {
        try {
            if (!$this->moduleHelper->isModuleEnabled() || !$this->moduleHelper->isModuleProperlyInstalled()) {
                $this->xtentoLogger->info('Cronjob executed, but module is disabled or not installed properly. Stopping.');
                return true;
            }
            if (!$schedule) {
                $this->xtentoLogger->info('Cronjob executed, but no schedule is defined for cron. Stopping.');
                return true;
            }
            $jobCode = $schedule->getJobCode();
            preg_match('/profile_(\d+)/', $jobCode, $jobMatch);
            if (!isset($jobMatch[1])) {
                throw new LocalizedException(__('No profile ID found in job_code.'));
            }
            $profileId = $jobMatch[1];
            $profile = $this->profileFactory->create()->load($profileId);
            if (!$profile->getId()) {
                // Remove existing cronjobs
                $this->cronHelper->removeCronjobsLike('orderimport_profile_' . $profileId . '_%', \Xtento\OrderImport\Cron\Import::CRON_GROUP);
                throw new LocalizedException(__('Profile ID %1 does not seem to exist anymore.', $profileId));
            }
            if (!$profile->getEnabled()) {
                return true; // Profile not enabled
            }
            if (!$profile->getCronjobEnabled()) {
                return true; // Cronjob not enabled
            }
            $importModel = $this->importFactory->create()->setProfile($profile);
            $importModel->cronImport();
        } catch (\Exception $e) {
            $this->xtentoLogger->critical('Cronjob exception for job_code ' . $jobCode . ': ' . $e->getMessage());
        }
        return true;
    }
}
