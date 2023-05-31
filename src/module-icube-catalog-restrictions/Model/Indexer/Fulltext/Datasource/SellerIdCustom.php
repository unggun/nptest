<?php

namespace Icube\CatalogRestrictions\Model\Indexer\Fulltext\Datasource;

class SellerIdCustom implements \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface
{
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Add seller id custom data to the elasticsuite index data.
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect('entity_id', 'row_id');
        $collection->addAttributeToSelect('seller_id', 'left');
        $selectColumnPart = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns([
                'id' => 'e.entity_id',
                'seller_id' => $selectColumnPart[1][0] . '.' . $selectColumnPart[1][1],
            ]);
        $products = $collection->toArray();
        $pairs = array_column($products, 'seller_id', 'id');
        foreach ($indexData as $productId => $product) {
            $indexData[$productId]['seller_id_match'] = $pairs[$productId] ?? 'null';
        }

        return $indexData;
    }
}
