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
namespace Aheadworks\CustomerAttributes\Test\Unit\ViewModel\Customer\Form;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\ViewModel\Customer\Form\Edit;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Form as FormAttributes;
use Magento\Store\Model\StoreResolver;

/**
 * Class EditTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\ViewModel\Customer\Form
 */
class EditTest extends TestCase
{
    /**
     * @var ElementFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $elementFactoryMock;

    /**
     * @var FormAttributes|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formAttributesMock;

    /**
     * @var StoreResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeResolverMock;

    /**
     * @var Form|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formMock;

    /**
     * @var CustomerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRegistryMock;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;

    /**
     * @var Edit
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
        $this->elementFactoryMock = $this->createMock(ElementFactory::class);
        $this->formAttributesMock = $this->createMock(FormAttributes::class);
        $this->storeResolverMock = $this->createMock(StoreResolver::class);
        $this->formMock = $this->createMock(Form::class);
        $formFactoryMock = $this->createConfiguredMock(FormFactory::class, ['create' => $this->formMock]);
        $this->customerRegistryMock = $this->createMock(CustomerRegistry::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->viewModel = $objectManager->getObject(
            Edit::class,
            [
                'elementFactory' => $this->elementFactoryMock,
                'formAttributes' => $this->formAttributesMock,
                'storeResolver' => $this->storeResolverMock,
                'formFactory' => $formFactoryMock,
                'customerRegistry' => $this->customerRegistryMock,
                'session' => $this->sessionMock
            ]
        );
    }

    /**
     * Test getAttributes method
     */
    public function testGetAttributes()
    {
        $attributeMock = $this->createConfiguredMock(Attribute::class, ['isStatic' => false]);
        $staticAttributeMock = $this->createConfiguredMock(Attribute::class, ['isStatic' => true]);

        $this->formAttributesMock->expects($this->once())
            ->method('setFormCode')
            ->willReturnSelf();
        $this->formAttributesMock->expects($this->once())
            ->method('getAllowedAttributes')
            ->willReturn([$attributeMock, $staticAttributeMock]);

        $this->assertEquals([$attributeMock], $this->viewModel->getAttributes());
    }

    /**
     * Test render method
     *
     * @param bool $isStatic
     * @param bool $usesSource
     * @dataProvider renderProvider
     */
    public function testRender($isStatic, $usesSource)
    {
        $html = $isStatic ? '' : '<div>some html content</div>';
        $attributeMock = $this->createConfiguredMock(
            Attribute::class,
            [
                'isStatic' => $isStatic,
                'usesSource' => $usesSource
            ]
        );
        $elementMock = $this->createMock(AbstractElement::class);

        $this->elementFactoryMock->expects($this->exactly($isStatic ? 0 : 1))
            ->method('create')
            ->willReturn($elementMock);
        $elementMock->expects($this->exactly($isStatic ? 0 : 1))
            ->method('setForm')
            ->willReturnSelf();
        $elementMock->expects($this->exactly($isStatic ? 0 : 1))
            ->method('setType')
            ->willReturnSelf();
        $elementMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $elementMock->expects($this->exactly($isStatic ? 0 : 1))
            ->method('toHtml')
            ->willReturn($html);
        $this->storeResolverMock->expects($this->exactly(($isStatic) ? 0 : 1))
            ->method('getCurrentStoreId')
            ->willReturn(1);

        $this->assertEquals($html, $this->viewModel->render($attributeMock));
    }

    /**
     * Test getValue method
     *
     * @param bool $trowException
     * @throws \ReflectionException
     * @dataProvider getValueProvider
     */
    public function testGetValue($trowException)
    {
        $class = new \ReflectionClass($this->viewModel);
        $method = $class->getMethod('getValue');
        $method->setAccessible(true);

        $customerId = 1;
        $customerData = ['customer_id' => $customerId, 'attribute1' => 'value1'];
        $customerMock = $this->createConfiguredMock(Customer::class, ['getData' => $customerData]);
        $attributeMock = $this->createMock(Attribute::class);
        $exception = new LocalizedException(__('Customer doesn\'t exits.'));

        $this->sessionMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($trowException ? null : $customerId);
        if ($trowException) {
            $this->customerRegistryMock->expects($this->once())
                ->method('retrieve')
                ->with(null)
                ->willThrowException($exception);
        } else {
            $this->customerRegistryMock->expects($this->once())
                ->method('retrieve')
                ->with($customerId)
                ->willReturn($customerMock);
        }

        $method->invoke($this->viewModel, $attributeMock);
    }

    /**
     * @return array
     */
    public function renderProvider()
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false]
        ];
    }

    /**
     * @return array
     */
    public function getValueProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
