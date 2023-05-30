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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model\Sales\AttributeData\FieldsetProcessor;

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\Composite;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\Order;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\OrderAddress;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\ProcessorInterface;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\Quote;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class CompositeTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Sales\AttributeData\FieldsetProcessor
 */
class CompositeTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManagerMock;

    /**
     * @var Composite
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
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->processor = $objectManager->getObject(
            Composite::class,
            [
                'objectManager' => $this->objectManagerMock
            ]
        );
    }

    /**
     * Test process method
     *
     * @param string $fieldsetName
     * @param string|null $processorClass
     * @dataProvider processProvider
     */
    public function testProcess($fieldsetName, $processorClass)
    {
        $result = ['some_attr_code' => ['to_entity' => '*']];
        $processorMock = $this->createMock(ProcessorInterface::class);

        $this->objectManagerMock->expects($this->exactly($processorClass ? 1 : 0))
            ->method('create')
            ->with($processorClass)
            ->willReturn($processorMock);
        $processorMock->expects($this->exactly($processorClass ? 1 : 0))
            ->method('process')
            ->with($result, $fieldsetName)
            ->willReturn($result);

        $this->assertEquals($result, $this->processor->process($result, $fieldsetName));
    }

    /**
     * @return array
     */
    public function processProvider()
    {
        return [
            ['sales_copy_order', Order::class],
            ['customer_account', Quote::class],
            ['sales_copy_order_billing_address', OrderAddress::class],
            ['some_fieldset_name', null]
        ];
    }
}
