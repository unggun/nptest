<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Icube\CatalogRestrictions\Model\Resolver\Products\Query;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\FieldSelection;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Search\QueryPopularity;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Suggestions;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Search\Api\SearchInterface;
use Magento\Search\Model\Search\PageSizeProvider;
use Smile\ElasticsuiteCatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;

class Search implements ProductQueryInterface
{
    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var ProductSearch
     */
    private $productProvider;

    /**
     * @var FieldSelection
     */
    private $fieldSelection;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    private ArgumentsProcessorInterface $argsSelection;

    private Suggestions $suggestions;

    private QueryPopularity $queryPopularity;

    /**
     * @param SearchInterface       $search                Search Engine
     * @param SearchResultFactory   $searchResultFactory   Search Results Factory
     * @param FieldSelection        $fieldSelection        Field Selection
     * @param ProductSearch         $productProvider       Product Provider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search Criteria Builder
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        FieldSelection $fieldSelection,
        ProductSearch $productProvider,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PageSizeProvider $pageSize,
        ArgumentsProcessorInterface $argsSelection = null,
        Suggestions $suggestions = null,
        QueryPopularity $queryPopularity = null
    ) {
        $this->search                = $search;
        $this->searchResultFactory   = $searchResultFactory;
        $this->fieldSelection        = $fieldSelection;
        $this->productProvider       = $productProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pageSizeProvider = $pageSize;
        $this->argsSelection = $argsSelection ?: ObjectManager::getInstance()
            ->get(ArgumentsProcessorInterface::class);
        $this->suggestions = $suggestions ?: ObjectManager::getInstance()
            ->get(Suggestions::class);
        $this->queryPopularity = $queryPopularity ?: ObjectManager::getInstance()->get(QueryPopularity::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(
        array $args,
        ResolveInfo $info,
        ContextInterface $context
    ): SearchResult {
        $args_seller = [];
        if(isset($args['filter']['seller_id'])){
            $args_seller = $args;
            // unset($args['filter']['seller_id']);
        }
        
        if (isset($args['filter']['seller_name'])){
            $args_seller = $args;
            unset($args['filter']['seller_name']);
        }

        $searchCriteria = $this->buildSearchCriteria($args, $info);

        $realPageSize = $searchCriteria->getPageSize();
        $realCurrentPage = $searchCriteria->getCurrentPage();
        //Because of limitations of sort and pagination on search API we will query all IDS
        $pageSize = $this->pageSizeProvider->getMaxPageSize();
        $searchCriteria->setPageSize($pageSize);
        $searchCriteria->setCurrentPage(0);
        $itemsResults = $this->search->search($searchCriteria);

        //Address limitations of sort and pagination on search API apply original pagination from GQL query
        $searchCriteria->setPageSize($realPageSize);
        $searchCriteria->setCurrentPage($realCurrentPage);
        $searchResults = $this->productProvider->getList(
            $searchCriteria,
            $itemsResults,
            $this->fieldSelection->getProductsFieldSelection($info),
            $context,
            $args_seller
        );

        $totalPages = $realPageSize ? ((int)ceil($searchResults->getTotalCount() / $realPageSize)) : 0;

        // add query statistics data
        if (!empty($args['search'])) {
            $this->queryPopularity->execute($context, $args['search'], (int) $searchResults->getTotalCount());
        }

        $productArray = [];
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($searchResults->getItems() as $product) {
            $productArray[$product->getId()] = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }

        $suggestions = [];
        $totalCount = (int) $searchResults->getTotalCount();
        if ($totalCount === 0 && !empty($args['search'])) {
            $suggestions = $this->suggestions->execute($context, $args['search']);
        }

        return $this->searchResultFactory->create(
            [
                'totalCount' => $totalCount,
                'productsSearchResult' => $productArray,
                'searchAggregation' => $itemsResults->getAggregations(),
                'pageSize' => $realPageSize,
                'currentPage' => $realCurrentPage,
                'totalPages' => $totalPages,
                'suggestions' => $suggestions,
            ]
        );
    }

    /**
     * Build search criteria from query input args
     *
     * @param array $args
     * @param ResolveInfo $info
     * @return SearchCriteriaInterface
     */
    private function buildSearchCriteria(array $args, ResolveInfo $info): SearchCriteriaInterface
    {
        $productFields = (array)$info->getFieldSelection(1);
        $includeAggregations = isset($productFields['filters']) || isset($productFields['aggregations']);
        if (isset($args['filter']['seller_id']['match'])) {
            $match = explode(',', $args['filter']['seller_id']['match']);
            $args['filter']['seller_id_match'] = ['in' => $match];
            unset($args['filter']['seller_id']);
        }
        $processedArgs = $this->argsSelection->process((string) $info->fieldName, $args);
        $searchCriteria = $this->searchCriteriaBuilder->build($processedArgs, $includeAggregations);

        return $searchCriteria;
    }
}
