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
namespace Aheadworks\CustomerAttributes\Test\Unit\Block\Adminhtml\Page\Menu;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Block\Adminhtml\Page\Menu\Item;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;

/**
 * Class ItemTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Block\Adminhtml\Page\Menu
 */
class ItemTest extends TestCase
{
    /**
     * @var Item
     */
    private $item;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $authorizationMock;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->requestMock = $this->createPartialMock(Http::class, ['getControllerName']);
        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->authorizationMock = $this->getMockForAbstractClass(AuthorizationInterface::class);
        $contextMock = $objectManager->getObject(
            Context::class,
            [
                'request' => $this->requestMock,
                'urlBuilder' => $this->urlBuilderMock,
                'authorization' => $this->authorizationMock
            ]
        );
        $this->item = $objectManager->getObject(
            Item::class,
            ['context' => $contextMock]
        );
    }

    /**
     * Testing of prepareLinkAttributes method for the use getUrl method
     */
    public function testPrepareLinkAttributes()
    {
        $linkAttributes = [
            'class' => 'separator',
        ];
        $path = '*/rule/index';

        $this->item->setLinkAttributes($linkAttributes);
        $this->item->setPath($path);

        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with($path);

        $class = new \ReflectionClass($this->item);
        $method = $class->getMethod('prepareLinkAttributes');
        $method->setAccessible(true);

        $method->invoke($this->item);
    }

    /**
     * Testing of serializeLinkAttributes method
     */
    public function testSerializeLinkAttributes()
    {
        $linkAttributes = [
            'attr' => 'attr_value',
            'attr_1' => 'attr_value_1',
        ];
        $expected = 'attr="attr_value" attr_1="attr_value_1"';
        $this->item->setLinkAttributes($linkAttributes);

        $this->assertEquals($expected, $this->item->serializeLinkAttributes());
    }

    /**
     * Testing of _toHtml method, resource is not allowed
     */
    public function testToHtml()
    {
        $resource = 'test';
        $expected = '';

        $this->authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with($resource)
            ->willReturn(false);
        $this->item->setResource($resource);

        $class = new \ReflectionClass($this->item);
        $method = $class->getMethod('_toHtml');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($this->item));
    }

    /**
     * Testing of isCurrent method
     *
     * @param string $controllerName
     * @param string $requestControllerName
     * @param bool $expected
     * @dataProvider isCurrentDataProvider
     */
    public function testIsCurrent($controllerName, $requestControllerName, $expected)
    {
        $this->requestMock->expects($this->once())
            ->method('getControllerName')
            ->willReturn($requestControllerName);
        $this->item->setControllers($controllerName);

        $class = new \ReflectionClass($this->item);
        $method = $class->getMethod('isCurrent');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($this->item));
    }

    /**
     * @return array
     */
    public function isCurrentDataProvider()
    {
        return [
            [['test'], 'test', true],
            [['test'], 'test_test', false]
        ];
    }
}
