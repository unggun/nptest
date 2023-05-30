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

use Aheadworks\CustomerAttributes\Model\Attribute\Provider;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\CollectionFactory;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;

/**
 * Class ProviderTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute
 */
class ProviderTest extends TestCase
{
    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->provider = $objectManager->getObject(
            Provider::class,
            [
                'collectionFactory' => $this->collectionFactoryMock
            ]
        );
    }

    /**
     * Test getOrderAttributeCodes method
     *
     * @param bool $isAttributeFits
     * @dataProvider boolProvider
     */
    public function testGetOrderAttributeCodes($isAttributeFits)
    {
        $attributeMock = $this->createMock(AttributeInterface::class);
        $collectionMock = $this->getCollectionMock([$attributeMock]);
        $code = 'test_attribute';
        $result = $isAttributeFits ? [AttributeResource::COLUMN_PREFIX . $code] : [];

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $attributeMock->expects($this->once())
            ->method('getUsedInOrderView')
            ->willReturn($isAttributeFits ? 1 : 0);
        $attributeMock->expects($this->exactly($isAttributeFits ? 1 : 0))
            ->method('getAttributeCode')
            ->willReturn($code);
        $attributeMock->expects($this->exactly($isAttributeFits ? 0 : 1))
            ->method('getUsedInForms')
            ->willReturn($isAttributeFits ? [UsedInForms::ADMIN_CHECKOUT] : []);

        $this->assertEquals($result, $this->provider->getOrderAttributeCodes());
    }

    /**
     * Test getOrderAddressAttributeCodes method
     *
     * @param bool $isAttributeFits
     * @dataProvider boolProvider
     */
    public function testGetOrderAddressAttributeCodes($isAttributeFits)
    {
        $attributeMock = $this->createMock(AttributeInterface::class);
        $collectionMock = $this->getCollectionMock([$attributeMock]);
        $code = 'test_attribute';
        $result = $isAttributeFits ? [$code] : [];

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $attributeMock->expects($this->once())
            ->method('getIsUserDefined')
            ->willReturn($isAttributeFits ? 1 : 0);
        $attributeMock->expects($this->exactly($isAttributeFits ? 1 : 0))
            ->method('getAttributeCode')
            ->willReturn($code);

        $this->assertEquals($result, $this->provider->getOrderAddressAttributeCodes());
    }

    /**
     * Test getOrderGridAttributes method
     *
     * @param bool $isAttributeFits
     * @dataProvider boolProvider
     */
    public function testGetOrderGridAttributes($isAttributeFits)
    {
        $attributeMock = $this->createMock(AttributeInterface::class);
        $collectionMock = $this->getCollectionMock([$attributeMock]);
        $result = $isAttributeFits ? [$attributeMock] : [];

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $attributeMock->expects($this->once())
            ->method('getUsedInOrderGrid')
            ->willReturn($isAttributeFits ? 1 : 0);

        $this->assertEquals($result, $this->provider->getOrderGridAttributes());
    }

    /**
     * Get collection mock
     *
     * @param AttributeInterface[]\PHPUnit_Framework_MockObject_MockObject[] $attributeMocks
     * @return Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCollectionMock($attributeMocks)
    {
        $collectionMock = $this->createMock(Collection::class);

        $collectionMock->expects($this->once())
            ->method('addSystemHiddenFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('addExcludeHiddenFrontendFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($attributeMocks));

        return $collectionMock;
    }

    /**
     * @return array
     */
    public function boolProvider()
    {
        return [[true], [false]];
    }
}
