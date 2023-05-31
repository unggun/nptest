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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model\PostData;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\PostData\ProcessorComposite;
use Aheadworks\CustomerAttributes\Model\PostData\ProcessorInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class ProcessorCompositeTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\PostData
 */
class ProcessorCompositeTest extends TestCase
{
    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $realProcessorMock;

    /**
     * @var ProcessorComposite
     */
    private $processor;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->realProcessorMock = $this->createMock(ProcessorInterface::class);
        $processors = [
            $this->realProcessorMock,
            new DataObject()
        ];
        $this->processor = $objectManager->getObject(
            ProcessorComposite::class,
            [
                'processors' => $processors
            ]
        );
    }

    /**
     * Test prepareData method
     */
    public function testPrepareDataBeforeSave()
    {
        $data = [AttributeInterface::ATTRIBUTE_CODE => 'test_code'];
        $preparedData = array_merge($data, [AttributeInterface::ENTITY_TYPE_CODE => 'customer']);

        $this->realProcessorMock->expects($this->once())
            ->method('process')
            ->with($data)
            ->willReturn($preparedData);

        $this->assertEquals($preparedData, $this->processor->prepareData($data));
    }
}
