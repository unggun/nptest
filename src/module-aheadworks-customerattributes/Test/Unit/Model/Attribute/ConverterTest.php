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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute;

use Aheadworks\CustomerAttributes\Model\Attribute\Converter;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterfaceFactory;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Class ConverterTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute
 */
class ConverterTest extends TestCase
{
    /**
     * @var AttributeInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeFactoryMock;

    /**
     * @var DataObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataObjectHelperMock;

    /**
     * @var DataObjectProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataObjectProcessorMock;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->attributeFactoryMock = $this->createMock(AttributeInterfaceFactory::class);
        $this->dataObjectHelperMock = $this->createMock(DataObjectHelper::class);
        $this->dataObjectProcessorMock = $this->createMock(DataObjectProcessor::class);
        $this->converter = $objectManager->getObject(
            Converter::class,
            [
                'attributeFactory' => $this->attributeFactoryMock,
                'dataObjectHelper' => $this->dataObjectHelperMock,
                'dataObjectProcessor' => $this->dataObjectProcessorMock
            ]
        );
    }

    /**
     * Test getDataObject method
     */
    public function testGetDataObject()
    {
        $attributeMock = $this->createMock(AttributeInterface::class);
        $attributeModelMock = $this->createMock(AttributeInterface::class);
        $data = [AttributeInterface::ATTRIBUTE_ID => 200];

        $this->attributeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributeMock);
        $this->dataObjectProcessorMock->expects($this->once())
            ->method('buildOutputDataArray')
            ->with($attributeModelMock, AttributeInterface::class)
            ->willReturn($data);
        $this->dataObjectHelperMock->expects($this->once())
            ->method('populateWithArray')
            ->with($attributeMock, $data, AttributeInterface::class)
            ->willReturnSelf();

        $this->assertSame($attributeMock, $this->converter->getDataObject($attributeModelMock));
    }

    /**
     * Test getDataObjectByFormData method
     */
    public function testGetDataObjectByFormData()
    {
        $attributeMock = $this->createMock(Attribute::class);
        $data = [AttributeInterface::ATTRIBUTE_ID => 200, AttributeInterface::WEBSITE => 1];

        $this->attributeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributeMock);
        $attributeMock->expects($this->once())
            ->method('addData')
            ->with($data)
            ->willReturnSelf();
        $attributeMock->expects($this->once())
            ->method('setWebsite')
            ->with($data[AttributeInterface::WEBSITE])
            ->willReturnSelf();

        $this->assertSame($attributeMock, $this->converter->getDataObjectByFormData($data));
    }
}
