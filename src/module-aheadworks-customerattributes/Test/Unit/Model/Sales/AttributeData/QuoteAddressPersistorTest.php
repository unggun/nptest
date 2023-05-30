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

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\QuoteAddressPersistor;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Aheadworks\CustomerAttributes\Model\Sales\Quote\Address;
use Aheadworks\CustomerAttributes\Model\Sales\Quote\AddressFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Quote\Address as AddressResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class QuoteAddressPersistorTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Sales\AttributeData
 */
class QuoteAddressPersistorTest extends TestCase
{
    /**
     * @var AddressFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressFactoryMock;

    /**
     * @var AddressResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressResourceMock;

    /**
     * @var QuoteAddressPersistor
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
        $this->addressFactoryMock = $this->createMock(AddressFactory::class);
        $this->addressResourceMock = $this->createMock(AddressResource::class);
        $this->persistor = $objectManager->getObject(
            QuoteAddressPersistor::class,
            [
                'addressFactory' => $this->addressFactoryMock,
                'addressResource' => $this->addressResourceMock
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
        $quoteAddressMock = $this->createMock(AbstractModel::class);
        $addressMock = $this->createMock(Address::class);
        $id = 1;
        $data = [
            'some_attribute_1' => 'value1',
            'some_attribute_2' => 'value2'
        ];
        $dataToSave = array_merge($data, [Attribute::ADDRESS_ID => $id]);
        $exception = new AlreadyExistsException(__('Already exist!'));

        $this->addressFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($addressMock);
        $quoteAddressMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $quoteAddressMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $addressMock->expects($this->once())
            ->method('addData')
            ->with($dataToSave)
            ->willReturnSelf();
        if ($throwException) {
            $this->addressResourceMock->expects($this->once())
                ->method('save')
                ->with($addressMock)
                ->willThrowException($exception);
            $this->expectException(AlreadyExistsException::class);
            $this->expectExceptionMessage('Already exist!');
        } else {
            $this->addressResourceMock->expects($this->once())
                ->method('save')
                ->with($addressMock)
                ->willReturnSelf();
        }

        $this->persistor->save($quoteAddressMock);
    }

    /**
     * Test load method
     */
    public function testLoad()
    {
        $quoteAddressMock = $this->createMock(AbstractModel::class);
        $addressMock = $this->createMock(Address::class);
        $id = 1;
        $data = [
            Attribute::ADDRESS_ID => $id,
            'some_attribute_1' => 'value1',
            'some_attribute_2' => 'value2'
        ];

        $this->addressFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($addressMock);
        $quoteAddressMock->expects($this->once())
            ->method('addData')
            ->with($data)
            ->willReturnSelf();
        $quoteAddressMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $addressMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $this->addressResourceMock->expects($this->once())
            ->method('load')
            ->with($addressMock, $id)
            ->willReturnSelf();

        $this->persistor->load($quoteAddressMock);
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [[true], [false]];
    }
}
