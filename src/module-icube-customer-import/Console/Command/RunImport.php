<?php
declare(strict_types=1);

namespace Icube\CustomerImport\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\Console\Cli;

/**
 * Command for Import Customer
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RunImport extends Command
{
    /** Command name */
    const NAME = 'icube:customer_import:run';

    const LIMIT = 'limit';
    const START_ROW = 'start_row';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
			new InputOption(
				self::LIMIT,
				null,
				InputOption::VALUE_OPTIONAL,
				'Limit'
            ),
            new InputOption(
				self::START_ROW,
				null,
				InputOption::VALUE_OPTIONAL,
				'Start Row'
			)
		];

        $this->setName(self::NAME)
            ->setDescription(
                'Import Customer.'
            )
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $startRow = $input->getOption(self::START_ROW);
            $limit = $input->getOption(self::LIMIT);

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
            $this->helper = $objectManager->get("Icube\CustomerImport\Helper\Data");
            $result = $this->helper->runImport($limit, $startRow);
            if ($result) {
                $output->writeln("<info>".$result."</info>");
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}