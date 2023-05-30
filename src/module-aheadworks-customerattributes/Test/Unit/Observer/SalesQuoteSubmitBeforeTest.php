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

use Aheadworks\CustomerAttributes\Model\Attribute\SalesDataCopier;
use Aheadworks\CustomerAttributes\Observer\SalesQuoteSubmitBefore;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class SalesQuoteSubmitBeforeTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Observer
 */
class SalesQuoteSubmitBeforeTest extends TestCase
{
    /**
     * @var SalesDataCopier|\PHPUnit_Framework_MockObject_MockObject
     */
    private $copierMock;

    /**
     * @var SalesQuoteSubmitBefore
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
        $this->copierMock = $this->createMock(SalesDataCopier::class);
        $this->observer = $objectManager->getObject(
            SalesQuoteSubmitBefore::class,
            [
                'salesDataCopier' => $this->copierMock
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $eventMock = $this->createMock(Event::class);
        $observerMock = $this->createConfiguredMock(Observer::class, ['getEvent' => $eventMock]);
        $quoteMock = $this->createMock(AbstractModel::class);
        $orderMock = $this->createMock(AbstractModel::class);

        $eventMock->expects($this->exactly(2))
            ->method('__call')
            ->willReturnMap([
                ['getQuote', [], $quoteMock],
                ['getOrder', [], $orderMock]
            ]);
        $this->copierMock->expects($this->once())
            ->method('fromQuoteToOrder')
            ->with($quoteMock, $orderMock);

        $this->observer->execute($observerMock);
    }
}
