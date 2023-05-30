<?php

namespace Icube\TierPrice\Controller\Import;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

class DownloadLog extends Action
{
    /**
     * @var DirectoryList
     */
    protected $directory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param DirectoryList $directory
     * @param FileFactory $fileFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        DirectoryList $directory,
        FileFactory $fileFactory,
        LoggerInterface $logger
    ) {
        $this->directory = $directory;
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $fileName = 'ProductTierPriceUpload-' . $this->getRequest()->getParam('file') . '.csv';
        $filePath = '';

        try {
            $filePath = $this->directory->getPath('var') . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'ProductTierPrice' . DIRECTORY_SEPARATOR . $fileName;
        } catch (FileSystemException $e) {
            $this->logger->info($e->getMessage());
        }

        try {
            return $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'filename',
                    'value' => $filePath
                ],
                \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                'application/octet-stream'
            );
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
