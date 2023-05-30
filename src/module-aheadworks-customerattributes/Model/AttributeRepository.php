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
namespace Aheadworks\CustomerAttributes\Model;

use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterfaceFactory;
use Aheadworks\CustomerAttributes\Api\Data\AttributeSearchResultsInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeSearchResultsInterfaceFactory;
use Aheadworks\CustomerAttributes\Model\Attribute\Converter;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResourceModel;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\CollectionFactory as AttributeCollectionFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Class AttributeRepository
 * @package Aheadworks\CustomerAttributes\Model
 */
class AttributeRepository implements AttributeRepositoryInterface
{
    /**
     * @var AttributeResourceModel
     */
    private $resource;

    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var AttributeSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var array
     */
    private $registry = [];

    /**
     * @param AttributeResourceModel $resource
     * @param AttributeInterfaceFactory $attributeFactory
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param AttributeSearchResultsInterfaceFactory $searchResultsFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param Converter $converter
     */
    public function __construct(
        AttributeResourceModel $resource,
        AttributeInterfaceFactory $attributeFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        AttributeSearchResultsInterfaceFactory $searchResultsFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        Converter $converter
    ) {
        $this->resource = $resource;
        $this->attributeFactory = $attributeFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(AttributeInterface $attribute)
    {
        try {
            $this->resource->save($attribute);
            $this->resource->addAttributeColumns($attribute);
            $this->registry[$attribute->getAttributeId()] = $attribute;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($attributeId)
    {
        if (!isset($this->registry[$attributeId])) {
            /** @var Attribute $attribute */
            $attribute = $this->attributeFactory->create();
            $this->resource->load($attribute, $attributeId);
            if (!$attribute->getAttributeId()) {
                throw NoSuchEntityException::singleField(AttributeInterface::ATTRIBUTE_ID, $attributeId);
            }
            $this->registry[$attributeId] = $attribute;
        }
        return $this->registry[$attributeId];
    }

    /**
     * {@inheritdoc}
     */
    public function getByCode($attributeCode)
    {
        /** @var Attribute $attribute */
        $attribute = $this->attributeFactory->create();
        $this->resource->load($attribute, $attributeCode, AttributeInterface::ATTRIBUTE_CODE);
        if (!$attribute->getAttributeId()) {
            throw NoSuchEntityException::singleField(AttributeInterface::ATTRIBUTE_CODE, $attributeCode);
        }
        $this->registry[$attribute->getAttributeId()] = $attribute;

        return $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var Collection $collection */
        $collection = $this->attributeCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process($collection, AttributeInterface::class);
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var AttributeSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());

        $objects = [];
        /** @var Attribute $item */
        foreach ($collection->getItems() as $item) {
            $objects[] = $this->converter->getDataObject($item);
        }
        $searchResults->setItems($objects);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeInterface $attribute)
    {
        try {
            $this->resource->delete($attribute);
            $this->resource->deleteAttributeColumns($attribute);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        if (isset($this->registry[$attribute->getAttributeId()])) {
            unset($this->registry[$attribute->getAttributeId()]);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($attributeId)
    {
        $attribute = $this->getById($attributeId);

        return $this->delete($attribute);
    }
}
