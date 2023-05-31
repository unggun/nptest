<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-21T14:45:42+00:00
 * File:          app/code/Xtento/OrderImport/Console/Command/ConfigExportCommand.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Console\Command;

use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigExportCommand extends Command
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var \Xtento\OrderImport\Helper\Tools
     */
    protected $toolsHelper;

    /**
     * ConfigExportCommand constructor.
     *
     * @param AppState $appState
     * @param \Xtento\OrderImport\Helper\Tools $toolsHelper
     */
    public function __construct(
        AppState $appState,
        \Xtento\OrderImport\Helper\Tools $toolsHelper
    ) {
        $this->appState = $appState;
        $this->toolsHelper = $toolsHelper;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('xtento:orderimport:config:export')
            ->setDescription('Export "XTENTO order import module" configuration as JSON file (functionality in admin: Order Import > Tools)')
            ->setDefinition(
                [
                    new InputArgument(
                        'file',
                        InputArgument::REQUIRED,
                        'File to save settings in. Example: /tmp/settings.json'
                    ),
                    new InputOption(
                        'profiles',
                        '-p',
                        InputOption::VALUE_OPTIONAL,
                        'Profile IDs to export (multiple IDs: comma-separated, no spaces)'
                    ),
                    new InputOption(
                        'sources',
                        '-s',
                        InputOption::VALUE_OPTIONAL,
                        'Source IDs to export (multiple IDs: comma-separated, no spaces)'
                    )
                ]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // intentionally left empty
        }
        echo(sprintf("[Debug] App Area: %s\n", $this->appState->getAreaCode())); // Required to avoid "area code not set" error

        $outputFile = $input->getArgument('file');
        $profileIds = explode(",", $input->getOption('profiles'));
        $profileIds = array_filter($profileIds, function($value) { return $value !== ''; });
        $sourceIds = explode(",", $input->getOption('sources'));
        $sourceIds = array_filter($sourceIds, function($value) { return $value !== ''; });

        if (empty($profileIds) && empty($sourceIds)) {
            $output->writeln("<error>Profile and source IDs missing. One of the two must be specified so something can be exported.</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        if (!empty($profileIds)) {
            $output->writeln(sprintf("<info>Profile IDs: %s</info>", implode(", ", $profileIds)));
        }
        if (!empty($sourceIds)) {
            $output->writeln(sprintf("<info>Source IDs: %s</info>", implode(", ", $sourceIds)));
        }

        $jsonConfiguration = $this->toolsHelper->exportSettingsAsJson($profileIds, $sourceIds);
        if (!file_put_contents($outputFile, $jsonConfiguration)) {
            $output->writeln(sprintf("<error>Could not write JSON configuration into file %s. File permissions?</error>", $outputFile));
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln(sprintf("<info>Finished export of configuration into file %s</info>", $outputFile));
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
