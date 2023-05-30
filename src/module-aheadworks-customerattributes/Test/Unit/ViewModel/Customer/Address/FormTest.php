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
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Form as FormAttributes;
use Magento\Store\Model\StoreResolver;
use Aheadworks\CustomerAttributes\ViewModel\Customer\Address\Form as AddressForm;

/**
 * Class FormTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\ViewModel\Customer\Form
 */
class FormTest extends TestCase
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
     * @var AddressRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressRegistryMock;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;
    
    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var AddressForm
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
        $this->addressRegistryMock = $this->createMock(AddressRegistry::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->viewModel = $objectManager->getObject(
            AddressForm::class,
            [
                'elementFactory' => $this->elementFactoryMock,
                'formAttributes' => $this->formAttributesMock,
                'storeResolver' => $this->storeResolverMock,
                'formFactory' => $formFactoryMock,
                'addressRegistry' => $this->addressRegistryMock,
                'session' => $this->sessionMock,
                'request' => $this->requestMock
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

        $this->mockGetAddress(false);
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

        $this->mockGetAddress(false);
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
     * Mock getAddress method
     *
     * @param bool $trowException
     */
    private function mockGetAddress($trowException)
    {
        $customerId = 1;
        $addressData = ['address_id' => 1, 'customer_id' => $customerId, 'attribute1' => 'value1'];
        $addressMock = $this->createConfiguredMock(
            Address::class,
            [
                'getData' => $addressData,
                'getCustomerId' => $customerId
            ]
        );
        $exception = new LocalizedException(__('Address doesn\'t exits.'));

        $this->sessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($trowException ? null : $customerId);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn(1);
        if ($trowException) {
            $this->addressRegistryMock->expects($this->any())
                ->method('retrieve')
                ->with(null)
                ->willThrowException($exception);
        } else {
            $this->addressRegistryMock->expects($this->any())
                ->method('retrieve')
                ->with($customerId)
                ->willReturn($addressMock);
        }
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
            [false],
            [true]
        ];
    }
}
