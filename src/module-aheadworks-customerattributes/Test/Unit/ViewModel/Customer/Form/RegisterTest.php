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
use Aheadworks\CustomerAttributes\ViewModel\Customer\Form\Register;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Customer\Model\Form as FormAttributes;
use Magento\Store\Model\StoreResolver;

/**
 * Class RegisterTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\ViewModel\Customer\Form
 */
class RegisterTest extends TestCase
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
     * @var Register
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
        $this->viewModel = $objectManager->getObject(
            Register::class,
            [
                'elementFactory' => $this->elementFactoryMock,
                'formAttributes' => $this->formAttributesMock,
                'storeResolver' => $this->storeResolverMock,
                'formFactory' => $formFactoryMock
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
}
