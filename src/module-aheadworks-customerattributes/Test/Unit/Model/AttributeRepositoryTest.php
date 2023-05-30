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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model;

use Aheadworks\CustomerAttributes\Api\Data\AttributeSearchResultsInterface;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\AttributeFactory;
use Aheadworks\CustomerAttributes\Model\AttributeRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterfaceFactory;
use Aheadworks\CustomerAttributes\Api\Data\AttributeSearchResultsInterfaceFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResourceModel;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection as AttributeCollection;

/**
 * Class AttributeRepository
 *
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model
 */
class AttributeRepositoryTest extends TestCase
{
    /**
     * @var AttributeRepository
     */
    private $model;

    /**
     * @var AttributeResourceModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    /**
     * @var AttributeInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeFactoryMock;

    /**
     * @var AttributeCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeCollectionFactoryMock;

    /**
     * @var AttributeSearchResultsInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchResultsFactoryMock;

    /**
     * @var JoinProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributesJoinProcessorMock;

    /**
     * @var CollectionProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessorMock;

    /**
     * @var Attribute\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converterMock;

    /**
     * @var array
     */
    private $attributeData = [
        'attribute_id' => 1,
        'code' => 'test_attribute'
    ];

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->resourceMock = $this->createMock(AttributeResourceModel::class);
        $this->attributeFactoryMock = $this->createMock(AttributeInterfaceFactory::class);
        $this->converterMock = $this->createMock(Attribute\Converter::class);
        $this->attributeCollectionFactoryMock = $this->createMock(AttributeCollectionFactory::class);
        $this->searchResultsFactoryMock = $this->createMock(AttributeSearchResultsInterfaceFactory::class);
        $this->extensionAttributesJoinProcessorMock = $this->createMock(JoinProcessorInterface::class);
        $this->collectionProcessorMock = $this->createMock(CollectionProcessorInterface::class);
        $this->model = $objectManager->getObject(
            AttributeRepository::class,
            [
                'resource' => $this->resourceMock,
                'attributeFactory' => $this->attributeFactoryMock,
                'attributeCollectionFactory' => $this->attributeCollectionFactoryMock,
                'searchResultsFactory' => $this->searchResultsFactoryMock,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessorMock,
                'collectionProcessor' => $this->collectionProcessorMock,
                'converter' => $this->converterMock
            ]
        );
    }

    /**
     * Testing of save method
     *
     * @throws CouldNotSaveException
     */
    public function testSave()
    {
        /** @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject $attributeMock */
        $attributeMock = $this->createMock(Attribute::class);

        $this->resourceMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->resourceMock->expects($this->once())
            ->method('addAttributeColumns')
            ->willReturnSelf();
        $attributeMock->expects($this->once())
            ->method('getAttributeId')
            ->willReturn($this->attributeData['attribute_id']);

        $this->assertSame($attributeMock, $this->model->save($attributeMock));
    }

    /**
     * Testing of save method on exception
     *
     * @throws CouldNotSaveException
     */
    public function testSaveOnException()
    {
        $this->expectExceptionMessage("Exception message.");
        $this->expectException(\Magento\Framework\Exception\CouldNotSaveException::class);
        $exception = new \Exception('Exception message.');

        /** @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject $attributeMock */
        $attributeMock = $this->createMock(Attribute::class);
        $this->resourceMock->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->model->save($attributeMock);
    }

    /**
     * Testing of getById method
     *
     * @throws NoSuchEntityException
     */
    public function testGetById()
    {
        $attributeId = 1;

        /** @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject $attributeMock */
        $attributeMock = $this->createMock(Attribute::class);
        $this->attributeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributeMock);

        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($attributeMock, $attributeId)
            ->willReturnSelf();
        $attributeMock->expects($this->once())
            ->method('getAttributeId')
            ->willReturn($attributeId);

        $this->assertSame($attributeMock, $this->model->getById($attributeId));
    }

    /**
     * Testing of getById method on exception
     *
     * @throws NoSuchEntityException
     */
    public function testGetOnException()
    {
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->expectExceptionMessage("No such entity with attribute_id = 20");
        $attributeId = 20;
        $attributeMock = $this->createMock(Attribute::class);
        $this->attributeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributeMock);

        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($attributeMock, $attributeId)
            ->willReturn(null);

        $this->model->getById($attributeId);
    }

    /**
     * Testing of getList method
     */
    public function testGetList()
    {
        $collectionSize = 1;
        /** @var AttributeCollection|\PHPUnit_Framework_MockObject_MockObject $attributeCollectionMock */
        $attributeCollectionMock = $this->createMock(AttributeCollection::class);
        /** @var SearchCriteriaInterface|\PHPUnit_Framework_MockObject_MockObject $searchCriteriaMock */
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $searchResultsMock = $this->createMock(AttributeSearchResultsInterface::class);
        /** @var Attribute|\PHPUnit_Framework_MockObject_MockObject $attributeModelMock */
        $attributeModelMock = $this->createMock(Attribute::class);
        /** @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);

        $this->attributeCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributeCollectionMock);
        $this->extensionAttributesJoinProcessorMock->expects($this->once())
            ->method('process')
            ->with($attributeCollectionMock, AttributeInterface::class);
        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $attributeCollectionMock);

        $attributeCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn($collectionSize);

        $this->searchResultsFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($searchResultsMock);
        $searchResultsMock->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteriaMock);
        $searchResultsMock->expects($this->once())
            ->method('setTotalCount')
            ->with($collectionSize);

        $attributeCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$attributeModelMock]);

        $this->converterMock->expects($this->once())
            ->method('getDataObject')
            ->with($attributeModelMock)
            ->willReturn($attributeMock);

        $searchResultsMock->expects($this->once())
            ->method('setItems')
            ->with([$attributeMock])
            ->willReturnSelf();

        $this->assertSame($searchResultsMock, $this->model->getList($searchCriteriaMock));
    }

    /**
     * Testing of getList method
     */
    public function testDeleteById()
    {
        $attributeId = '123';

        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock->expects($this->any())
            ->method('getAttributeId')
            ->willReturn($attributeId);
        $this->attributeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($attributeMock);
        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($attributeMock, $attributeId)
            ->willReturnSelf();
        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->with($attributeMock)
            ->willReturn(true);
        $this->resourceMock->expects($this->once())
            ->method('deleteAttributeColumns')
            ->willReturnSelf();

        $this->assertTrue($this->model->deleteById($attributeId));
    }

    /**
     * Testing of delete method on exception
     *
     * @throws CouldNotDeleteException
     */
    public function testDeleteException()
    {
        $this->expectException(\Magento\Framework\Exception\CouldNotDeleteException::class);
        /** @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject $attributeMock */
        $attributeMock = $this->createMock(Attribute::class);
        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->with($attributeMock)
            ->willThrowException(new \Exception());

        $this->model->delete($attributeMock);
    }
}
