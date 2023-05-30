<?php

namespace Icube\CatalogRestrictions\Model\Resolver\ProductDataProvider;

use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessor;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch\ProductCollectionSearchCriteriaBuilder;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;

class ProductSearch extends \Icube\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionPreProcessor;

    /**
     * @var CollectionPostProcessor
     */
    private $collectionPostProcessor;

    /**
     * @var SearchResultApplierFactory;
     */
    private $searchResultApplierFactory;

    /**
     * @var ProductCollectionSearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionPreProcessor
     * @param CollectionPostProcessor $collectionPostProcessor
     * @param SearchResultApplierFactory $searchResultsApplierFactory
     * @param ProductCollectionSearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Reports\Model\Event\TypeFactory $eventTypeFactory
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param StoreManager $storeManager
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionPreProcessor,
        CollectionPostProcessor $collectionPostProcessor,
        SearchResultApplierFactory $searchResultsApplierFactory,
        ProductCollectionSearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Reports\Model\Event\TypeFactory $eventTypeFactory,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\Config $catalogConfig,
        StoreManager $storeManager,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionPreProcessor = $collectionPreProcessor;
        $this->collectionPostProcessor = $collectionPostProcessor;
        $this->searchResultApplierFactory = $searchResultsApplierFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct(
            $collectionFactory,
            $searchResultsFactory,
            $collectionPreProcessor,
            $collectionPostProcessor,
            $searchResultsApplierFactory,
            $searchCriteriaBuilder,
            $localeDate,
            $resource,
            $eventTypeFactory,
            $catalogProductVisibility,
            $catalogConfig,
            $storeManager,
            $productAttributeRepository
        );
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        SearchResultInterface $searchResult,
        array $attributes = [],
        ContextInterface $context = null,
        array $args = []
    ): SearchResultsInterface {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection = $this->_addProductAttributesAndPrices($collection)->addStoreFilter($this->_storeManager->getStore()->getId());
        //Create a copy of search criteria without filters to preserve the results from search
        $searchCriteriaForCollection = $this->searchCriteriaBuilder->build($searchCriteria);
        //Apply CatalogSearch results from search and join table
        $this->getSearchResultsApplier(
            $searchResult,
            $collection,
            $this->getSortOrderArray($searchCriteriaForCollection)
        )->apply();

        if (isset($args['filter'])) {
            if (count($args['filter']) == 1 && empty($args['search'])
               && (isset($args['filter']['seller_id']) || isset($args['filter']['seller_name']))
            ) {
                $collection->getSelect()->reset(\Zend_Db_Select::WHERE);
            }

            foreach ($args['filter'] as $key => $item) {
                if ($key == 'seller_id') {
                    $attribute_id = $this->productAttributeRepository->get($key)->getAttributeId();
                    $joinTableName = $collection->getTable('catalog_product_entity_varchar');
                    $filter = 'e.entity_id = ' . $joinTableName . '.entity_id AND ' . $joinTableName . '.attribute_id=' . $attribute_id;
                    $where = $joinTableName . '.value = ' . "'" . $item['match'] . "'";
                    if (strpos($item['match'], ',') !== false) {
                        $multiMatch = str_replace(',', "','", $item['match']);
                        $where = $joinTableName . ".value IN ('" . $multiMatch . "')";
                    }

                    $collection->getSelect()->joinInner(
                        $joinTableName,
                        $filter,
                        [$joinTableName . '.value']
                    )->where($where);
                }

                if ($key == 'seller_name') {
                    $attribute_id = $this->productAttributeRepository->get($key)->getAttributeId();
                    $joinTableName = $collection->getTable('catalog_product_entity_varchar');
                    $filter = 'e.entity_id = ' . $joinTableName . '.entity_id AND ' . $joinTableName . '.attribute_id=' . $attribute_id;
                    $where = $joinTableName . '.value LIKE ' . "'%" . $item['match'] . "%'";

                    $collection->getSelect()->joinInner(
                        $joinTableName,
                        $filter,
                        [$joinTableName . '.value']
                    )->where($where);
                }
            }
        }

        $sortType = $this->getSortOrderArray($searchCriteriaForCollection);

        if (isset($sortType["latest"])) {
            if ($sortType["latest"] == "ASC") {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->getSelect()->order('created_at ASC');
            } else {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->getSelect()->order('created_at DESC');
            }
        }

        if (isset($sortType["new"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $this->_getNewProductCollection($collection, $sortType);
        }

        if (isset($sortType["bestseller"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $this->_getBestSellerProductCollection($collection, $this->_storeManager->getStore()->getId(), $sortType);
        }

        if (isset($sortType["onsale"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $this->_getOnsaleProductCollection($collection, $this->_storeManager->getStore()->getId(), $sortType);
        }

        if (isset($sortType["mostviewed"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $this->_getMostViewedProductCollection($collection, $this->_storeManager->getStore()->getId(), $sortType);
        }

        if (isset($sortType["wishlisttop"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $this->_getWishlisttopProductCollection($collection, $this->_storeManager->getStore()->getId(), $sortType);
        }

        if (isset($sortType["free"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $collection->getSelect()->where('price_index.price = ?', 0);
            $collection->addAttributeToFilter('type_id', [
                'in' => [
                    \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                    \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
                    \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
                ]
            ]);
        }

        if (isset($sortType["featured"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $collection->addAttributeToFilter('featured', ['eq' => 1]);
        }

        if (isset($sortType["toprated"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $this->_getTopRatedProductCollection($collection, $this->_storeManager->getStore()->getId(), $sortType);
        }

        if (isset($sortType["random"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $collection->getSelect()->order('RAND()');
        }

        if (isset($sortType["alphabetically"])) {
            if ($sortType["alphabetically"] == "ASC") {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('name', 'ASC');
            } else {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('name', 'DESC');
            }
        }

        if (isset($sortType["price"])) {
            if ($sortType["price"] == "ASC") {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('price', 'ASC');
            } else {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('price', 'DESC');
            }
        }

        if (isset($sortType["random_by"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $collection->getSelect()->order('RAND()');
        }

        if (isset($sortType["new_old"])) {
            if ($sortType["new_old"] == "ASC") {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('entity_id', 'ASC');
            } else {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('entity_id', 'DESC');
            }
        }

        if (isset($sortType["product_attr"])) {
            if ($sortType["product_attr"] == "ASC") {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('product_position', 'ASC');
            } else {
                $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
                $collection->setOrder('product_position', 'DESC');
            }
        }

        if (isset($sortType["new_arrivals"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            if ($sortType["new_arrivals"] == "ASC") {
                $collection->setOrder('created_at', 'ASC');
            } else {
                $collection->setOrder('created_at', 'DESC');
            }
            $collection->setOrder('entity_id', 'ASC');
        }

        if (isset($sortType["top_seller"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $joinTableName = $collection->getTable('sales_order_item');
            if ($sortType["top_seller"] == "ASC") {
                $collection->getSize();
                $collection->getSelect()->joinLeft(
                    $joinTableName,
                    'e.entity_id = ' . $joinTableName . '.product_id AND ' . $joinTableName . '.store_id=' . $this->_storeManager->getStore()->getId(),
                    [$joinTableName . '.qty_ordered' => 'SUM(' . $joinTableName . '.qty_ordered) AS ordered']
                )
                ->group('e.entity_id')
                ->order('entity_id ASC');
            } else {
                $collection->getSize();
                $collection->getSelect()->joinLeft(
                    $joinTableName,
                    'e.entity_id = ' . $joinTableName . '.product_id AND ' . $joinTableName . '.store_id=' . $this->_storeManager->getStore()->getId(),
                    [$joinTableName . '.qty_ordered' => 'SUM(' . $joinTableName . '.qty_ordered) AS ordered']
                )
                ->group('e.entity_id')
                ->order('entity_id DESC');
            }
        }

        if (isset($sortType["top_rated"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $joinTableName = $collection->getTable('rating_option_vote_aggregated');
            if ($sortType["top_rated"] == "ASC") {
                $collection->getSelect()->joinLeft(
                    $joinTableName,
                    'e.entity_id = ' . $joinTableName . '.entity_pk_value AND ' . $joinTableName . '.store_id=' . $this->_storeManager->getStore()->getId(),
                    [$joinTableName . '.percent_approved']
                )
                ->order('percent_approved ASC')
                ->order('entity_id ASC');
            } else {
                $collection->getSelect()->joinLeft(
                    $joinTableName,
                    'e.entity_id = ' . $joinTableName . '.entity_pk_value AND ' . $joinTableName . '.store_id=' . $this->_storeManager->getStore()->getId(),
                    [$joinTableName . '.percent_approved']
                )
                ->order('percent_approved DESC')
                ->order('entity_id ASC');
            }
        }

        if (isset($sortType["most_reviewed"])) {
            $collection->getSelect()->reset(\Zend_Db_Select::ORDER);
            $joinTableName = $collection->getTable('review_entity_summary');
            if ($sortType["most_reviewed"] == "ASC") {
                $collection->getSelect()->joinLeft(
                    $joinTableName,
                    'e.entity_id = ' . $joinTableName . '.entity_pk_value AND ' . $joinTableName . '.store_id=' . $this->_storeManager->getStore()->getId(),
                    [$joinTableName . '.reviews_count']
                )
                ->order('reviews_count ASC')
                ->order('entity_id ASC');
            } else {
                $collection->getSelect()->joinLeft(
                    $joinTableName,
                    'e.entity_id = ' . $joinTableName . '.entity_pk_value AND ' . $joinTableName . '.store_id=' . $this->_storeManager->getStore()->getId(),
                    [$joinTableName . '.reviews_count']
                )
                ->order('reviews_count DESC')
                ->order('entity_id ASC');
            }
        }

        $collection->setFlag('search_resut_applied', true);
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInSiteIds());
        $this->collectionPreProcessor->process($collection, $searchCriteriaForCollection, $attributes, $context);
        $collection->load();
        $this->collectionPostProcessor->process($collection, $attributes);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteriaForCollection);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Create searchResultApplier
     *
     * @param SearchResultInterface $searchResult
     * @param Collection $collection
     * @param array $orders
     * @return SearchResultApplierInterface
     */
    private function getSearchResultsApplier(
        SearchResultInterface $searchResult,
        Collection $collection,
        array $orders
    ): SearchResultApplierInterface {
        return $this->searchResultApplierFactory->create(
            [
                'collection' => $collection,
                'searchResult' => $searchResult,
                'orders' => $orders
            ]
        );
    }

    /**
     * Format sort orders into associative array
     *
     * E.g. ['field1' => 'DESC', 'field2' => 'ASC", ...]
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    private function getSortOrderArray(SearchCriteriaInterface $searchCriteria)
    {
        $ordersArray = [];
        $sortOrders = $searchCriteria->getSortOrders();
        if (is_array($sortOrders)) {
            foreach ($sortOrders as $sortOrder) {
                // I am replacing _id with entity_id because in ElasticSearch _id is required for sorting by ID.
                // Where as entity_id is required when using ID as the sort in $collection->load();.
                // @see \Magento\CatalogGraphQl\Model\Resolver\Products\Query\Search::getResult
                if ($sortOrder->getField() === '_id') {
                    $sortOrder->setField('entity_id');
                }
                $ordersArray[$sortOrder->getField()] = $sortOrder->getDirection();
            }
        }

        return $ordersArray;
    }
}
