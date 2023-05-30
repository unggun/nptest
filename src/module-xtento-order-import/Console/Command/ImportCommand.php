<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-10-04T12:33:04+00:00
 * File:          app/code/Xtento/OrderImport/Console/Command/ImportCommand.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Console\Command;

use Magento\Framework\App\State as AppState;
use Magento\Framework\App\AreaList as AreaList;
use Magento\Framework\App\Area as Area;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var AreaList
     */
    protected $areaList;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Xtento\OrderImport\Model\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Xtento\OrderImport\Model\ImportFactory
     */
    protected $importFactory;

    /**
     * ImportCommand constructor.
     *
     * @param AppState $appState
     * @param AreaList $areaList
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Xtento\OrderImport\Model\ProfileFactory $profileFactory
     * @param \Xtento\OrderImport\Model\ImportFactory $importFactory
     */
    public function __construct(
        AppState $appState,
        AreaList $areaList,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Xtento\OrderImport\Model\ProfileFactory $profileFactory,
        \Xtento\OrderImport\Model\ImportFactory $importFactory
    ) {
        $this->appState = $appState;
        $this->areaList = $areaList;
        $this->objectManager = $objectManager;
        $this->profileFactory = $profileFactory;
        $this->importFactory = $importFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('xtento:orderimport:import')
            ->setDescription('Import XTENTO tracking import profile.')
            ->setDefinition(
                [
                    new InputArgument(
                        'profile',
                        InputArgument::REQUIRED,
                        'Profile IDs to import (multiple IDs: comma-separated)'
                    )
                ]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(Area::AREA_CRONTAB);
            $configLoader = $this->objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class);
            $this->objectManager->configure($configLoader->load(Area::AREA_CRONTAB));
            $this->areaList->getArea(Area::AREA_CRONTAB)->load(Area::PART_TRANSLATE);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // intentionally left empty
        }
        echo(sprintf("[Debug] App Area: %s\n", $this->appState->getAreaCode())); // Required to avoid "area code not set" error

        $profileIds = explode(",", $input->getArgument('profile'));
        if (empty($profileIds)) {
            $output->writeln("<error>Profile IDs to import missing.</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        foreach ($profileIds as $profileId) {
            $profileId = intval($profileId);
            if ($profileId < 1) {
                $output->writeln("<error>Invalid profile ID: %s</error>", $profileId);
                continue;
            }

            try {
                $profile = $this->profileFactory->create()->load($profileId);
                if (!$profile->getId()) {
                    $output->writeln(sprintf("<error>Profile ID %d does not exist.</error>", $profileId));
                    continue;
                }
                if (!$profile->getEnabled()) {
                    $output->writeln(sprintf("<error>Profile ID %d is disabled.</error>", $profileId));
                    continue;
                }

                $output->writeln(sprintf("<info>Importing profile ID %d.</info>", $profileId));
                $importModel = $this->importFactory->create()->setProfile($profile);
                // Import
                $importModel->cronImport();
                $output->writeln(sprintf('<info>Import for profile ID %d completed. Check "Execution Log" for detailed results.</info>', $profileId));
            } catch (\Exception $exception) {
                $output->writeln(sprintf("<error>Exception for profile ID %d: %s</error>", $profileId, $exception->getMessage()));
                continue;
            }
        }
        $output->writeln("<info>Finished command.</info>");
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
