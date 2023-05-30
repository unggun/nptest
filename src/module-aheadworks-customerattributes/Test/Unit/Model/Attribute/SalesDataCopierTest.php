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

use Aheadworks\CustomerAttributes\Model\Attribute\Provider;
use Aheadworks\CustomerAttributes\Model\Attribute\SalesDataCopier;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class SalesDataCopierTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute
 */
class SalesDataCopierTest extends TestCase
{
    /**
     * @var Provider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributesProviderMock;

    /**
     * @var SalesDataCopier
     */
    private $copier;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->attributesProviderMock = $this->createMock(Provider::class);
        $this->copier = $objectManager->getObject(
            SalesDataCopier::class,
            [
                'attributesProvider' => $this->attributesProviderMock
            ]
        );
    }

    /**
     * Test fromQuoteToOrder method
     *
     * @param array $orderAttributeCodes
     * @param array $orderAddressAttributeCodes
     * @dataProvider fromQuoteToOrderProvider
     */
    public function testFromQuoteToOrder($orderAttributeCodes, $orderAddressAttributeCodes)
    {
        $entityCallsCount = count($orderAttributeCodes);
        $addressEntityCallsCount = count($orderAddressAttributeCodes);
        $quoteShippingMock = $this->createMock(QuoteAddress::class);
        $quoteBillingMock = $this->createMock(QuoteAddress::class);
        $orderShippingMock = $this->createMock(OrderAddress::class);
        $orderBillingMock = $this->createMock(OrderAddress::class);
        $quoteMock = $this->createConfiguredMock(
            Quote::class,
            [
                'getShippingAddress' => $quoteShippingMock,
                'getBillingAddress' => $quoteBillingMock
            ]
        );
        $orderMock = $this->createConfiguredMock(
            Order::class,
            [
                'getShippingAddress' => $orderShippingMock,
                'getBillingAddress' => $orderBillingMock
            ]
        );

        $this->attributesProviderMock->expects($this->once())
            ->method('getOrderAttributeCodes')
            ->willReturn($orderAttributeCodes);
        $this->attributesProviderMock->expects($this->once())
            ->method('getOrderAddressAttributeCodes')
            ->willReturn($orderAddressAttributeCodes);
        $quoteMock->expects($this->exactly($entityCallsCount))
            ->method('getData');
        $orderMock->expects($this->exactly($entityCallsCount))
            ->method('setData');
        $quoteShippingMock->expects($this->exactly($addressEntityCallsCount))
            ->method('getData');
        $orderShippingMock->expects($this->exactly($addressEntityCallsCount))
            ->method('setData');
        $quoteBillingMock->expects($this->exactly($addressEntityCallsCount))
            ->method('getData');
        $orderBillingMock->expects($this->exactly($addressEntityCallsCount))
            ->method('setData');

        $this->copier->fromQuoteToOrder($quoteMock, $orderMock);
    }

    /**
     * @return array
     */
    public function fromQuoteToOrderProvider()
    {
        return [
            [[], []],
            [['entity_attribute_1'], []],
            [[], ['address_attribute_1']],
            [['entity_attribute_1'], ['address_attribute_1']]
        ];
    }
}
