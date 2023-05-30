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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model\Sales\AttributeData;

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\OrderPersistor;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Aheadworks\CustomerAttributes\Model\Sales\Order;
use Aheadworks\CustomerAttributes\Model\Sales\OrderFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order as OrderResource;

/**
 * Class OrderPersistorTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Sales\AttributeData
 */
class OrderPersistorTest extends TestCase
{
    /**
     * @var OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderFactoryMock;

    /**
     * @var OrderResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderResourceMock;

    /**
     * @var OrderPersistor
     */
    private $persistor;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->orderResourceMock = $this->createMock(OrderResource::class);
        $this->persistor = $objectManager->getObject(
            OrderPersistor::class,
            [
                'orderFactory' => $this->orderFactoryMock,
                'orderResource' => $this->orderResourceMock
            ]
        );
    }

    /**
     * Test save method
     *
     * @param bool $throwException
     * @throws AlreadyExistsException
     * @dataProvider boolProvider
     */
    public function testSave($throwException)
    {
        $salesOrderMock = $this->createMock(AbstractModel::class);
        $orderMock = $this->createMock(Order::class);
        $id = 1;
        $data = [
            'some_attribute_1' => 'value1',
            'some_attribute_2' => 'value2'
        ];
        $dataToSave = array_merge($data, [Attribute::ORDER_ID => $id]);
        $exception = new AlreadyExistsException(__('Already exist!'));

        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);
        $salesOrderMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $salesOrderMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $orderMock->expects($this->once())
            ->method('addData')
            ->with($dataToSave)
            ->willReturnSelf();
        if ($throwException) {
            $this->orderResourceMock->expects($this->once())
                ->method('save')
                ->with($orderMock)
                ->willThrowException($exception);
            $this->expectException(AlreadyExistsException::class);
            $this->expectExceptionMessage('Already exist!');
        } else {
            $this->orderResourceMock->expects($this->once())
                ->method('save')
                ->with($orderMock)
                ->willReturnSelf();
        }

        $this->persistor->save($salesOrderMock);
    }

    /**
     * Test load method
     */
    public function testLoad()
    {
        $salesOrderMock = $this->createMock(AbstractModel::class);
        $orderMock = $this->createMock(Order::class);
        $id = 1;
        $data = [
            Attribute::ORDER_ID => $id,
            'some_attribute_1' => 'value1',
            'some_attribute_2' => 'value2'
        ];

        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);
        $salesOrderMock->expects($this->once())
            ->method('addData')
            ->with($data)
            ->willReturnSelf();
        $salesOrderMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $orderMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $this->orderResourceMock->expects($this->once())
            ->method('load')
            ->with($orderMock, $id)
            ->willReturnSelf();

        $this->persistor->load($salesOrderMock);
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [[true], [false]];
    }
}
