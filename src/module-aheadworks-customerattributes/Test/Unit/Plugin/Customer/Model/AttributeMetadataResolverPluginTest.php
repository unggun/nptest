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
namespace Aheadworks\CustomerAttributes\Test\Unit\Plugin\Customer\Model;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Aheadworks\CustomerAttributes\Plugin\Customer\Model\AttributeMetadataResolverPlugin;
use Magento\Customer\Model\AttributeMetadataResolver;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class AttributeMetadataResolverPluginTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Plugin\Customer\Model
 */
class AttributeMetadataResolverPluginTest extends TestCase
{
    /**
     * @var AttributeMetadataResolverPlugin
     */
    private $plugin;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(AttributeMetadataResolverPlugin::class);
    }

    /**
     * Test afterGetAttributesMeta method
     *
     * @param string $inputType
     * @dataProvider afterGetAttributesMetaProvider
     */
    public function testAfterGetAttributesMeta($inputType)
    {
        $resolverMock = $this->createMock(AttributeMetadataResolver::class);
        $attributeMock = $this->createMock(Attribute::class);
        $result = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => 'test'
                    ]
                ]
            ]
        ];
        $processedResult = $result;
        if ($inputType == InputType::FILE || $inputType == InputType::IMAGE) {
            $processedResult['arguments']['data']['config']['dataType'] = 'file';
        }

        $attributeMock->expects($this->once())
            ->method('getFrontendInput')
            ->willReturn($inputType);

        $this->assertEquals(
            $processedResult,
            $this->plugin->afterGetAttributesMeta($resolverMock, $result, $attributeMock)
        );
    }

    /**
     * @return array
     */
    public function afterGetAttributesMetaProvider()
    {
        return [
            [InputType::FILE],
            [InputType::IMAGE],
            [InputType::DATE]
        ];
    }
}
