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

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\QuotePersistor;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Aheadworks\CustomerAttributes\Model\Sales\Quote;
use Aheadworks\CustomerAttributes\Model\Sales\QuoteFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Quote as QuoteResource;

/**
 * Class QuotePersistorTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Sales\AttributeData
 */
class QuotePersistorTest extends TestCase
{
    /**
     * @var QuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteFactoryMock;

    /**
     * @var QuoteResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteResourceMock;

    /**
     * @var QuotePersistor
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
        $this->quoteFactoryMock = $this->createMock(QuoteFactory::class);
        $this->quoteResourceMock = $this->createMock(QuoteResource::class);
        $this->persistor = $objectManager->getObject(
            QuotePersistor::class,
            [
                'quoteFactory' => $this->quoteFactoryMock,
                'quoteResource' => $this->quoteResourceMock
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
        $salesQuoteMock = $this->createMock(AbstractModel::class);
        $quoteMock = $this->createMock(Quote::class);
        $id = 1;
        $data = [
            'some_attribute_1' => 'value1',
            'some_attribute_2' => 'value2'
        ];
        $dataToSave = array_merge($data, [Attribute::QUOTE_ID => $id]);
        $exception = new AlreadyExistsException(__('Already exist!'));

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock);
        $salesQuoteMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $salesQuoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $quoteMock->expects($this->once())
            ->method('addData')
            ->with($dataToSave)
            ->willReturnSelf();
        if ($throwException) {
            $this->quoteResourceMock->expects($this->once())
                ->method('save')
                ->with($quoteMock)
                ->willThrowException($exception);
            $this->expectException(AlreadyExistsException::class);
            $this->expectExceptionMessage('Already exist!');
        } else {
            $this->quoteResourceMock->expects($this->once())
                ->method('save')
                ->with($quoteMock)
                ->willReturnSelf();
        }

        $this->persistor->save($salesQuoteMock);
    }

    /**
     * Test load method
     */
    public function testLoad()
    {
        $salesQuoteMock = $this->createMock(AbstractModel::class);
        $quoteMock = $this->createMock(Quote::class);
        $id = 1;
        $data = [
            Attribute::QUOTE_ID => $id,
            'some_attribute_1' => 'value1',
            'some_attribute_2' => 'value2'
        ];

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteMock);
        $salesQuoteMock->expects($this->once())
            ->method('addData')
            ->with($data)
            ->willReturnSelf();
        $salesQuoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $quoteMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $this->quoteResourceMock->expects($this->once())
            ->method('load')
            ->with($quoteMock, $id)
            ->willReturnSelf();

        $this->persistor->load($salesQuoteMock);
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [[true], [false]];
    }
}
