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
namespace Aheadworks\CustomerAttributes\Test\Unit\Plugin\Framework\View\Element\UiComponent\DataProvider;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order\AttributeJoinProcessor;
use Aheadworks\CustomerAttributes\Plugin\Framework\View\Element\UiComponent\DataProvider\CollectionFactoryPlugin;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CollectionFactoryPluginTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Plugin\Framework\View\Element\UiComponent\DataProvider
 */
class CollectionFactoryPluginTest extends TestCase
{
    /**
     * @var CollectionFactoryPlugin
     */
    private $plugin;

    /**
     * @var AttributeJoinProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeJoinProcessorMock;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->attributeJoinProcessorMock = $this->createMock(AttributeJoinProcessor::class);
        $this->plugin = $objectManager->getObject(
            CollectionFactoryPlugin::class,
            ['attributeJoinProcessor' => $this->attributeJoinProcessorMock]
        );
    }

    /**
     * Test afterGetReport method
     *
     * @param string $requestName
     * @dataProvider afterGetReportProvider
     */
    public function testAfterGetReport($requestName)
    {
        $collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $collectionMock = $this->createMock(Collection::class);

        $this->attributeJoinProcessorMock
            ->expects($this->exactly($requestName == CollectionFactoryPlugin::TRIGGER_NAME ? 1 : 0))
            ->method('joinAttributeValues')
            ->with($collectionMock)
            ->willReturn($collectionMock);

        $this->assertSame(
            $collectionMock,
            $this->plugin->afterGetReport($collectionFactoryMock, $collectionMock, $requestName)
        );
    }

    /**
     * @return array
     */
    public function afterGetReportProvider()
    {
        return [
            [CollectionFactoryPlugin::TRIGGER_NAME],
            ['some_request_name']
        ];
    }
}
