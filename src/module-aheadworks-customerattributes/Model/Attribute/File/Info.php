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

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\FileProcessor;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Customer\Model\FileProcessorFactory;

/**
 * Class Info
 * @package Aheadworks\CustomerAttributes\Model\Attribute\File
 */
class Info
{
    /**
     * @var string
     */
    const FILE_DIR = 'customer';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var FileProcessor
     */
    private $fileProcessor;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @param Filesystem $filesystem
     * @param UrlInterface $urlBuilder
     * @param FileProcessorFactory $fileProcessorFactory
     * @param EncoderInterface $urlEncoder
     */
    public function __construct(
        Filesystem $filesystem,
        UrlInterface $urlBuilder,
        FileProcessorFactory $fileProcessorFactory,
        EncoderInterface $urlEncoder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->filesystem = $filesystem;
        $this->fileProcessor = $fileProcessorFactory->create(
            ['entityTypeCode' => CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER]
        );
        $this->urlEncoder = $urlEncoder;
    }

    /**
     * Get file statistics data
     *
     * @param string $fileName
     * @return array
     * @throws FileSystemException
     */
    public function getStat($fileName)
    {
        return $this->getMediaDirectory()->stat($this->getFilePath($fileName));
    }

    /**
     * Get WriteInterface instance
     *
     * @return WriteInterface
     * @throws FileSystemException
     */
    public function getMediaDirectory()
    {
        if ($this->mediaDirectory === null) {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        }
        return $this->mediaDirectory;
    }

    /**
     * Retrieve mime type
     *
     * @param string $file
     * @return string
     */
    public function getMimeType($file)
    {
        return $this->fileProcessor->getMimeType($file);
    }

    /**
     * Check if exist
     *
     * @param string $file
     * @return bool
     */
    public function isExist($file)
    {
        return $this->fileProcessor->isExist($file);
    }

    /**
     * Retrieve file url
     *
     * @param string $file
     * @param string $type
     * @return string
     */
    public function getUrl($file, $type)
    {
        return $this->urlBuilder->getUrl(
            'aw_customer_attributes/customer/viewfile',
            [$type => $this->urlEncoder->encode(ltrim($file, '/'))]
        );
    }

    /**
     * Get file path
     *
     * @param string $fileName
     * @return string
     */
    private function getFilePath($fileName)
    {
        return self::FILE_DIR . '/' . ltrim($fileName, '/');
    }
}
