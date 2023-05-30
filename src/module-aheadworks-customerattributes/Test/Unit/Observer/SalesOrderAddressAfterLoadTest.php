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

use Aheadworks\CustomerAttributes\Observer\SalesOrderAddressAfterLoad;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\OrderAddressPersistor;

/**
 * Class SalesOrderAddressAfterLoadTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Observer
 */
class SalesOrderAddressAfterLoadTest extends TestCase
{
    /**
     * @var OrderAddressPersistor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistorMock;

    /**
     * @var SalesOrderAddressAfterLoad
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
        $this->persistorMock = $this->createMock(OrderAddressPersistor::class);
        $this->observer = $objectManager->getObject(
            SalesOrderAddressAfterLoad::class,
            [
                'orderAddressPersistor' => $this->persistorMock
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
        $addressMock = $this->createMock(AbstractModel::class);

        $eventMock->expects($this->once())
            ->method('__call')
            ->with('getAddress')
            ->willReturn($addressMock);
        $this->persistorMock->expects($this->once())
            ->method('load')
            ->with($addressMock);

        $this->observer->execute($observerMock);
    }
}
