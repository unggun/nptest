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
namespace Aheadworks\CustomerAttributes\Test\Unit\Plugin\Framework\DataObject\Copy;

use Aheadworks\CustomerAttributes\Plugin\Framework\DataObject\Copy\ConfigPlugin;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\Composite as FieldsetProcessor;
use Magento\Framework\DataObject\Copy\Config;

/**
 * Class ConfigPluginTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Plugin\Framework\DataObject\Copy
 */
class ConfigPluginTest extends TestCase
{
    /**
     * @var ConfigPlugin
     */
    private $plugin;

    /**
     * @var FieldsetProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldsetProcessorMock;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->fieldsetProcessorMock = $this->createMock(FieldsetProcessor::class);
        $this->plugin = $objectManager->getObject(
            ConfigPlugin::class,
            ['fieldsetProcessor' => $this->fieldsetProcessorMock]
        );
    }

    /**
     * Test afterGetFieldset method
     *
     * @param bool $isChangedResult
     * @dataProvider boolProvider
     */
    public function testAfterGetFieldset($isChangedResult)
    {
        $configMock = $this->createMock(Config::class);
        $result = ['attribute1' => 'to_entity'];
        $fieldsetName = 'some_fieldset_name';
        $processedResult = $result;
        if ($isChangedResult) {
            $processedResult[] = ['attribute2' => 'to_entity'];
        }

        $this->fieldsetProcessorMock->expects($this->once())
            ->method('process')
            ->with($result, $fieldsetName)
            ->willReturn($processedResult);

        $this->assertEquals($processedResult, $this->plugin->afterGetFieldset($configMock, $result, $fieldsetName));
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
