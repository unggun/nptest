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
namespace Aheadworks\CustomerAttributes\Test\Unit\ViewModel\Customer;

use Aheadworks\CustomerAttributes\Model\Attribute\RelationLoader;
use Aheadworks\CustomerAttributes\ViewModel\Customer\EmailData;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class EmailDataTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\ViewModel\Customer
 */
class EmailDataTest extends TestCase
{
    /**
     * @var EmailData
     */
    private $viewModel;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->viewModel = $objectManager->getObject(EmailData::class);
    }

    /**
     * Test getAttributeValue method
     *
     * @param DataObject|\PHPUnit_Framework_MockObject_MockObject|null
     * @param string|null $attributeCode
     * @param string $value
     * @dataProvider getAttributeValueProvider
     */
    public function testGetAttributeValue($customerMock, $attributeCode, $value)
    {
        $this->assertEquals($value, $this->viewModel->getAttributeValue($customerMock, $attributeCode));
    }

    /**
     * @return array
     */
    public function getAttributeValueProvider()
    {
        $mainAttributeCode = 'attribute1';
        $mainValue = 'Some string value';
        $customAttributeArray = ['attribute_code' => $mainAttributeCode, 'value' => $mainValue];
        $customAttributeObject = $this->createMock(DataObject::class);
        $customerWithArrayMock = $this->createMock(DataObject::class);
        $customerWithObjectMock = $this->createMock(DataObject::class);

        $customerWithArrayMock->expects($this->any())
            ->method('getData')
            ->with('custom_attributes', null)
            ->willReturn([$customAttributeArray]);
        $customerWithObjectMock->expects($this->any())
            ->method('getData')
            ->with('custom_attributes', null)
            ->willReturn([$customAttributeObject]);
        $customAttributeObject->expects($this->any())
            ->method('__call')
            ->willReturnMap([
                ['getAttributeCode', [], $mainAttributeCode],
                ['getValue', [], $mainValue]
            ]);

        return [
            [null, null, ''],
            [$customerWithArrayMock, null, ''],
            [$customerWithObjectMock, null, ''],
            [null, $mainAttributeCode, ''],
            [$customerWithObjectMock, 'attribute2', ''],
            [$customerWithObjectMock, $mainAttributeCode, $mainValue],
            [$customerWithArrayMock, $mainAttributeCode, $mainValue]
        ];
    }
}
