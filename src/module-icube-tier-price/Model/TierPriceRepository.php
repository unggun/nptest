<?php

declare(strict_types=1);

namespace Icube\TierPrice\Model;

use Exception;
use Icube\TierPrice\Api\TierPriceRepositoryInterface;
use Icube\TierPrice\Model\TierPrice;
use Icube\TierPrice\Model\TierPriceFactory;
use Icube\TierPrice\Model\ResourceModel\TierPrice as ResourceTierPrice;
use Icube\TierPrice\Model\ResourceModel\TierPrice\CollectionFactory as TierPriceCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Api\SearchCriteriaInterface;

class TierPriceRepository implements TierPriceRepositoryInterface
{
    /**
     * @param ResourceTierPrice $resource
     * @param TierPriceCollectionFactory $tierPriceCollectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param TierPriceFactory $tierPriceFactory
     */
    public function __construct(
        ResourceTierPrice $resource,
        TierPriceCollectionFactory $tierPriceCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        TierPriceFactory $tierPriceFactory
    ) {
        $this->resource = $resource;
        $this->tierPriceCollectionFactory = $tierPriceCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->tierPriceFactory = $tierPriceFactory;
    }

    /**
     * @inheritDoc
     */
    public function save(TierPrice $tierPrice) 
    {
        try {
            $this->resource->save($tierPrice);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the Tier Price: %1',$exception->getMessage()));
        }
        return $tierPrice;
    }

    /**
     * @inheritDoc
     */
    public function get($tierDiscountId)
    {
        $tierPrice = $this->tierPriceFactory->create();
        $this->resource->load($tierPrice, $tierDiscountId);
        return $tierPrice;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $criteria) {
        $collection = $this->tierPriceCollectionFactory->create();
        $collection->getSelect();
        /*
        $collection
        ->join(
            'company_advanced_customer_entity',
            'main_table.customer_id = company_advanced_customer_entity.customer_id',
            ''
        )
        ->join(
            'company',
            'company_advanced_customer_entity.company_id = company.entity_id',
            'company.company_name'
        );
        */
        
        // $collection->addFilterToMap('company_name', 'company.company_name');
        // $collection->addFilterToMap('customer_group_code', 'customer_group.customer_group_code');
        // $collection->addFilterToMap('email', 'customer_entity.email');
        // $collection->addFilterToMap('customer_group_id', 'main_table.customer_group_id');
        
        $this->collectionProcessor->process($criteria, $collection);
        // $collection->printLogQuery(true);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        TierPrice $tierPrice
    ) {
        try {
            if(empty($tierPrice->getTierDiscountId())){
                return false;
            }
            $tierPriceModel = $this->tierPriceFactory->create();
            $this->resource->load($tierPriceModel, $tierPrice->getTierDiscountId());
            $this->resource->delete($tierPriceModel);
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($tierDiscountId)
    {
        return $this->delete($this->get($tierDiscountId));
    }
}
