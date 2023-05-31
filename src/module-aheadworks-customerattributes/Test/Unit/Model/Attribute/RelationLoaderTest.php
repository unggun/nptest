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

use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\RelationLoader;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;

/**
 * Class RelationLoaderTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute
 */
class RelationLoaderTest extends TestCase
{
    /**
     * @var AttributeResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeResourceMock;

    /**
     * @var RelationLoader
     */
    private $relationLoader;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->attributeResourceMock = $this->createMock(AttributeResource::class);
        $this->relationLoader = $objectManager->getObject(
            RelationLoader::class,
            [
                'attributeResource' => $this->attributeResourceMock
            ]
        );
    }

    /**
     * Test getRelationsData method
     *
     * @param bool $prepareForJs
     * @dataProvider boolProvider
     */
    public function testGetRelationsData($prepareForJs)
    {
        $relationsData = [
            [
                AttributeRelationInterface::ATTRIBUTE_CODE => 'test1',
                AttributeRelationInterface::OPTION_VALUE => 1,
                AttributeRelationInterface::DEPENDENT_ATTRIBUTE_CODE => 'test2'
            ],
            [
                AttributeRelationInterface::ATTRIBUTE_CODE => 'test1',
                AttributeRelationInterface::OPTION_VALUE => 2,
                AttributeRelationInterface::DEPENDENT_ATTRIBUTE_CODE => 'test3'
            ]
        ];
        $prepared = $prepareForJs
            ? [
                'test1' => [
                    1 => ['test2'],
                    2 => ['test3']
                ]
            ]
            : $relationsData;

        $this->attributeResourceMock->expects($this->once())
            ->method('loadAllRelationsData')
            ->willReturn($relationsData);

        $this->assertEquals($prepared, $this->relationLoader->getRelationsData($prepareForJs));
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [[true], [false]];
    }
}
