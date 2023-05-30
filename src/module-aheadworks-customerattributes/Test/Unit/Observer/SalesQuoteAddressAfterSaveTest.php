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
namespace Aheadworks\CustomerAttributes\Test\Unit\Observer;

use Aheadworks\CustomerAttributes\Observer\SalesQuoteAddressAfterSave;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\QuoteAddressPersistor;

/**
 * Class SalesQuoteAddressAfterSaveTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Observer
 */
class SalesQuoteAddressAfterSaveTest extends TestCase
{
    /**
     * @var QuoteAddressPersistor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistorMock;

    /**
     * @var SalesQuoteAddressAfterSave
     */
    private $observer;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->persistorMock = $this->createMock(QuoteAddressPersistor::class);
        $this->observer = $objectManager->getObject(
            SalesQuoteAddressAfterSave::class,
            [
                'quoteAddressPersistor' => $this->persistorMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @param bool $throwException
     * @throws AlreadyExistsException
     * @dataProvider boolProvider
     */
    public function testExecute($throwException)
    {
        $eventMock = $this->createMock(Event::class);
        $observerMock = $this->createConfiguredMock(Observer::class, ['getEvent' => $eventMock]);
        $addressMock = $this->createMock(AbstractModel::class);
        $exception = new AlreadyExistsException(__('Already exist!'));

        $eventMock->expects($this->once())
            ->method('__call')
            ->with('getQuoteAddress')
            ->willReturn($addressMock);
        if ($throwException) {
            $this->persistorMock->expects($this->once())
                ->method('save')
                ->with($addressMock)
                ->willThrowException($exception);
            $this->expectException(AlreadyExistsException::class);
            $this->expectExceptionMessage('Already exist!');
        } else {
            $this->persistorMock->expects($this->once())
                ->method('save')
                ->with($addressMock);
        }

        $this->observer->execute($observerMock);
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [[true], [false]];
    }
}
