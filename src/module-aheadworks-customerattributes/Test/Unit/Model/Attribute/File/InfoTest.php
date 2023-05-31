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
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\FileProcessor;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Customer\Model\FileProcessorFactory;

/**
 * Class InfoTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute\File
 */
class InfoTest extends TestCase
{
    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystemMock;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var FileProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileProcessorMock;

    /**
     * @var WriteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mediaDirectoryMock;

    /**
     * @var EncoderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlEncoderMock;

    /**
     * @var Info
     */
    private $info;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->fileProcessorMock = $this->createMock(FileProcessor::class);
        $this->urlEncoderMock = $this->createMock(EncoderInterface::class);
        $this->mediaDirectoryMock = $this->createMock(WriteInterface::class);
        $fileProcessorFactoryMock = $this->createConfiguredMock(
            FileProcessorFactory::class,
            ['create' => $this->fileProcessorMock]
        );
        $this->info = $objectManager->getObject(
            Info::class,
            [
                'urlBuilder' => $this->urlBuilderMock,
                'filesystem' => $this->filesystemMock,
                'fileProcessorFactory' => $fileProcessorFactoryMock,
                'urlEncoder' => $this->urlEncoderMock
            ]
        );
    }

    /**
     * Test getStat method
     *
     * @throws FileSystemException
     */
    public function testGetStat()
    {
        $fileName = 'test.png';
        $stat = ['size' => 2080, 'name' => $fileName];

        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->mediaDirectoryMock);
        $this->mediaDirectoryMock->expects($this->once())
            ->method('stat')
            ->with(Info::FILE_DIR . '/' . $fileName)
            ->willReturn($stat);

        $this->assertEquals($stat, $this->info->getStat($fileName));
    }

    /**
     * Test getMediaDirectory method
     *
     * @throws FileSystemException
     */
    public function testGetMediaDirectory()
    {
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::MEDIA)
            ->willReturn($this->mediaDirectoryMock);

        $this->assertSame($this->mediaDirectoryMock, $this->info->getMediaDirectory());
    }

    /**
     * Test getMimeType method
     */
    public function testGetMimeType()
    {
        $fileName = 'test.png';
        $mimeType = 'image/png';

        $this->fileProcessorMock->expects($this->once())
            ->method('getMimeType')
            ->willReturn($mimeType);

        $this->assertEquals($mimeType, $this->info->getMimeType($fileName));
    }

    /**
     * Test isExist method
     *
     * @param bool $isExist
     * @dataProvider boolProvider
     */
    public function testIsExist($isExist)
    {
        $fileName = 'test.png';

        $this->fileProcessorMock->expects($this->once())
            ->method('isExist')
            ->with($fileName)
            ->willReturn($isExist);

        $this->assertEquals($isExist, $this->info->isExist($fileName));
    }

    /**
     * Test getUrl method
     *
     * @param string $type
     * @dataProvider getUrlProvider
     */
    public function testGetUrl($type)
    {
        $fileName = 'test.png';
        $encodedFileName = strtr(base64_encode($fileName), '+/=', '-_,');
        $url = 'http://domain.com/aw_customer_attributes/customer/viewfile/' . $type . $encodedFileName;

        $this->urlEncoderMock->expects($this->once())
            ->method('encode')
            ->with($fileName)
            ->willReturn($encodedFileName);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('aw_customer_attributes/customer/viewfile', [$type => $encodedFileName])
            ->willReturn($url);

        $this->assertEquals($url, $this->info->getUrl($fileName, $type));
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @return array
     */
    public function getUrlProvider()
    {
        return [
            ['file'],
            ['image']
        ];
    }
}
