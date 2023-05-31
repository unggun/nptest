<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Model\Attribute\File;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;

/**
 * Class Provider
 * @package Aheadworks\CustomerAttributes\Model\Attribute\File
 */
class Provider
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var array
     */
    private $imageExtensions = [
        'png', 'jpg', 'gif', 'jpeg'
    ];

    /**
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     * @param RawFactory $rawFactory
     */
    public function __construct(
        Filesystem $filesystem,
        FileFactory $fileFactory,
        RawFactory $rawFactory
    ) {
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->resultRawFactory = $rawFactory;
    }

    /**
     * Download file
     *
     * @param string $filePath
     * @return ResponseInterface
     * @throws LocalizedException
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function download($filePath)
    {
        $file = $this->getFilePath($filePath);
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        if (!$dir->isFile($file)) {
            throw new LocalizedException(__('File not found.'));
        }
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
        return $this->fileFactory->create(
            $fileName,
            ['type' => 'filename', 'value' => $file],
            DirectoryList::MEDIA
        );
    }

    /**
     * Read file
     *
     * @param string $filePath
     * @return Raw
     * @throws LocalizedException
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function read($filePath)
    {
        $file = $this->getFilePath($filePath);
        $dir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$dir->isFile($file)) {
            throw new LocalizedException(__('File not found.'));
        }
        $fileContent = $dir->readFile($file);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $contentType = in_array(strtolower($ext), $this->imageExtensions)
            ? 'image/' . $ext
            : 'application/octet-stream';

        /** @var Raw $resultRaw */
        $result = $this->resultRawFactory->create();
        $result->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', $dir->stat($file)['size'] ?? 0)
            ->setHeader('Last-Modified', date('r'));
        $result->setContents($fileContent);

        return $result;
    }

    /**
     * Retrieve full file path
     *
     * @param string $fileName
     * @return string
     */
    private function getFilePath($fileName)
    {
        $DS = DIRECTORY_SEPARATOR;

        return Info::FILE_DIR . $DS . $fileName;
    }
}
