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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute\File;

use Aheadworks\CustomerAttributes\Model\Attribute\File\Info;
use Aheadworks\CustomerAttributes\Model\Attribute\File\Provider;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Filesystem;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;

/**
 * Class ProviderTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute\File
 */
class ProviderTest extends TestCase
{
    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystemMock;

    /**
     * @var FileFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileFactoryMock;

    /**
     * @var RawFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultRawFactoryMock;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->fileFactoryMock = $this->createMock(FileFactory::class);
        $this->resultRawFactoryMock = $this->createMock(RawFactory::class);
        $this->provider = $objectManager->getObject(
            Provider::class,
            [
                'filesystem' => $this->filesystemMock,
                'fileFactory' => $this->fileFactoryMock,
                'resultRawFactory' => $this->resultRawFactoryMock
            ]
        );
    }

    /**
     * Test download method
     *
     * @throws LocalizedException
     */
    public function testDownload()
    {
        $fileName = 'test.png';
        $filePath = Info::FILE_DIR . '/' . $fileName;
        $dirMock = $this->createMock(Filesystem\Directory\ReadInterface::class);
        $fileMock = $this->createMock(ResponseInterface::class);

        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($dirMock);
        $dirMock->expects($this->once())
            ->method('isFile')
            ->with($filePath)
            ->willReturn(true);
        $this->fileFactoryMock->expects($this->once())
            ->method('create')
            ->with($fileName, ['type' => 'filename', 'value' => $filePath], DirectoryList::MEDIA)
            ->willReturn($fileMock);

        $this->assertSame($fileMock, $this->provider->download($fileName));
    }

    /**
     * Test download method with exception
     *
     * @throws LocalizedException
     */
    public function testDownloadWithException()
    {
        $this->expectExceptionMessage("File not found.");
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $fileName = 'test.png';
        $filePath = Info::FILE_DIR . '/' . $fileName;
        $dirMock = $this->createMock(Filesystem\Directory\ReadInterface::class);

        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($dirMock);
        $dirMock->expects($this->once())
            ->method('isFile')
            ->with($filePath)
            ->willReturn(false);

        $this->provider->download($fileName);
    }

    /**
     * Test read method
     *
     * @throws LocalizedException
     */
    public function testRead()
    {
        $fileName = 'test.doc';
        $filePath = Info::FILE_DIR . '/' . $fileName;
        $dirMock = $this->createMock(Filesystem\Directory\ReadInterface::class);
        $rawMock = $this->createMock(Raw::class);
        $fileContent = 'some file content';

        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($dirMock);
        $dirMock->expects($this->once())
            ->method('isFile')
            ->with($filePath)
            ->willReturn(true);
        $dirMock->expects($this->once())
            ->method('readFile')
            ->with($filePath)
            ->willReturn($fileContent);
        $this->resultRawFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rawMock);
        $rawMock->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(200)
            ->willReturnSelf();
        $rawMock->expects($this->atLeastOnce())
            ->method('setHeader')
            ->withAnyParameters()
            ->willReturnSelf();
        $rawMock->expects($this->atLeastOnce())
            ->method('setContents')
            ->withAnyParameters()
            ->willReturnSelf();

        $this->assertSame($rawMock, $this->provider->read($fileName));
    }

    /**
     * Test read method with exception
     *
     * @throws LocalizedException
     */
    public function testReadWithException()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage("File not found.");
        $fileName = 'test.doc';
        $filePath = Info::FILE_DIR . '/' . $fileName;
        $dirMock = $this->createMock(Filesystem\Directory\ReadInterface::class);

        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($dirMock);
        $dirMock->expects($this->once())
            ->method('isFile')
            ->with($filePath)
            ->willReturn(false);

        $this->provider->read($fileName);
    }
}
