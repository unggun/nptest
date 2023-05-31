<?php
namespace Icube\TierPrice\Model\Indexer;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\Table\StrategyInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Indexer\Model\ResourceModel\AbstractResource;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Icube\TierPrice\Model\TierPriceFactory;

class TierPrice extends AbstractResource implements IdentityInterface
{
    public function __construct(
        Context $context,
        StrategyInterface $tableStrategy,
        EventManagerInterface $eventManager,
        TimezoneInterface $timezoneInterface,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        ProductRepository $productRepository,
        ResourceConnection $resourceConnection,
        ProductCollection $productCollection,
        TierPriceFactory $tierPriceFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $tableStrategy, $connectionName);
        $this->eventManager = $eventManager;
        $this->timezoneInterface = $timezoneInterface;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->productCollection = $productCollection;
        $this->tierPriceFactory = $tierPriceFactory;
    }

    protected function _construct()
    {
        $this->_init('icube_tier_price', 'id');
    }

    public function reindexAll()
    {
        $this->tableStrategy->setUseIdxTable(true);
        $oldData = $this->getDataFromIndex();
        $this->clearTemporaryIndexTable();

        $this->beginTransaction();
        try {
            $newData = $this->getTierPriceData();
            $this->saveDataToIndex($newData);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
        $this->dispatchCleanCacheByTags(array_merge($oldData, $newData));

        return $this;
    }

    public function reindexRows($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $newData = $this->getTierPriceData($ids);
        $this->beginTransaction();
        try {
            $condition = $this->getConnection()
                ->prepareSqlCondition('product_id', ['in' => $ids]);
            $oldData = $this->getDataFromIndex($condition);

            $this->removeRowsFromTable('product_id', $ids);
            $this->saveDataToIndex($newData, false);
            $this->commit();
            $this->dispatchCleanCacheByTags(array_merge($oldData, $newData));
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
        return $this;
    }

    private function getDataFromIndex($condition = null)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                'icube_tier_price_idx',
                'product_id'
            )
            ->group('product_id');

        if ($condition) {
            $select->where($condition);
        }

        return $connection->fetchAll($select);
    }

    public function clearTemporaryIndexTable()
    {
        $this->getConnection()->truncateTable('icube_tier_price_idx');
    }

    private function dispatchCleanCacheByTags($entities = [])
    {
        $this->entities = $entities;
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this]);
    }

    public function getIdentities()
    {
        $identities = [];
        foreach ($this->entities as $entity) {
            $identities[] = ProductModel::CACHE_TAG . '_' . $entity['product_id'];
        }

        return array_unique($identities);
    }

    public function getTierPriceData($ids = null)
    {
        $datas = [];
        $customers = [];

        $dateNow = $this->timezoneInterface->date()->format('Y-m-d');
        $tierpriceData = $this->tierPriceFactory->create()->getCollection()
                        ->addFieldToFilter('step_qty', ['eq' => 1])
                        ->addFieldToFilter('apply_to_price', ['eq' => 1])
                        ->addFieldToFilter(['start_date','start_date'], [['lteq' => $dateNow], ['null' => 'this_value_doesnt_matter']])
                        ->addFieldToFilter(['end_date','end_date'], [['gteq' => $dateNow], ['null' => 'this_value_doesnt_matter']]);

        if ($ids) {
            $skus = [];
            foreach ($ids as $id) {
                $sku = $this->getSkuProduct($id);
                if ($sku) {
                    $skus[] = $this->getSkuProduct($id);
                }
            }

            if ($skus) {
                $tierpriceData->addFieldToFilter('product_sku', ['in' => $skus]);
            }
        }

        $idx = 0;
        foreach ($tierpriceData as $tierPrice) {
            try {
                $product = $this->productRepository->get($tierPrice->getProductSku());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e){
                $product = false;
            }
            if ($product) {
                if ($tierPrice->getCustomerId() && ($tierPrice->getCustomerId() !== '*')) {
                    $any = strpos($tierPrice->getCustomerId(), ',');
                    if ($any == false) {
                        $datas = $this->getTierpriceIndex($datas, $tierPrice->getCustomerId(), $tierPrice, $product, 1);
                    } else {
                        $customerIds = explode(',', $tierPrice->getCustomerId());
                        $groupIds = explode(',', $tierPrice->getCustomerGroupId());
                        foreach ($customerIds as $customerId) {
                            $customer = $this->customerRepository->getById($customerId);
                            $isAny = array_search((int)$customer->getGroupId(), $groupIds);
                            if (($tierPrice->getCustomerGroupId() == '*') || ($isAny !== false)) {
                                $datas = $this->getTierpriceIndex($datas, $customerId, $tierPrice, $product, 2);
                            }
                        }
                    }
                } else {
                    if ($tierPrice->getCustomerGroupId() == '*') { // priority = 4
                        $customers[$idx]['tierPrice'] = $tierPrice;
                        $customers[$idx]['product'] = $product;
                        $customers[$idx]['customerId'] = null;
                    } else {  // priority = 3
                        $dataCustomers = $this->customerFactory->create()->getCollection()->addAttributeToSelect('*')
                                    ->addAttributeToFilter('group_id', ['in' => explode(',', $tierPrice->getCustomerGroupId())])
                                    ->load();
                        $customers[$idx]['tierPrice'] = $tierPrice;
                        $customers[$idx]['product'] = $product;
                        $customers[$idx]['customerId'] = implode(",",$dataCustomers->getAllIds());
                    }
                    $idx++;
                }
            }
        }

        if (!empty($customers)) {
            $allCustomers = $this->customerFactory->create()->getCollection()->addAttributeToSelect('*')->load();
            foreach ($allCustomers as $keys => $customer) {
                foreach ($customers as $key => $value) {
                    if ( empty($value['customerId']) && ($value['customerId'] !== $customer->getId()) ) {
                        $datas = $this->getTierpriceIndex($datas, $customer->getId(), $value['tierPrice'], $value['product'], 4);
                    } elseif ($value['customerId'] === $customer->getId()) {    
                        $datas = $this->getTierpriceIndex($datas, $customer->getId(), $value['tierPrice'], $value['product'], 3);
                    }
                }
            }
        }

        foreach ($datas as $key => $value) {
            unset($datas[$key]['priority']);
        }

        return $datas;
    }

    private function search($array, $search_list)
    {
        $result = [];
        foreach ($array as $key => $value) {
            foreach ($search_list as $k => $v) {
                if (!isset($value[$k]) || $value[$k] != $v) {
                    continue 2;
                }
            }

            $result[] = [
                'index' => $key,
                'priority' => $value['priority'],
                'final_price' => $value['final_price']
            ];
        }

        return $result;
    }

    private function getTierpriceIndex($datas, $customerId, $tierPrice, $product, $priority)
    {
        if ($datas) {
            $searchItem = [
                'customer_id' => $customerId,
                'product_id' => $product->getId()
            ];

            $searchResult = $this->search($datas, $searchItem);
            if ($searchResult) {
                foreach ($searchResult as $indexSearch) {
                    if ((int)$priority <= (int)$indexSearch['priority']) {
                        $discount = $this->getDiscount($tierPrice, $product->getPrice());
                        // $final_price = (int)$indexSearch['final_price'] - $discount;
                        $final_price = $product->getPrice() - $discount;
                        unset($datas[$indexSearch['index']]);
                        $datas[] = $this->setTierPriceIndex($customerId, $product->getId(), $final_price, $priority);
                    }
                }
            } else {
                $discount = $this->getDiscount($tierPrice, $product->getPrice());
                $final_price = $product->getPrice() - $discount;
                $datas[] = $this->setTierPriceIndex($customerId, $product->getId(), $final_price, $priority);
            }
        } else {
            $discount = $this->getDiscount($tierPrice, $product->getPrice());
            $final_price = $product->getPrice() - $discount;
            $datas[] = $this->setTierPriceIndex($customerId, $product->getId(), $final_price, $priority);
        }

        return $datas;
    }

    private function getDiscount($tierPrice, $productPrice)
    {
        if ($tierPrice->getDiscountPercentage() && ($tierPrice->getDiscountPercentage() > 0)) {
            $discount = ((int) $productPrice * $tierPrice->getDiscountPercentage()) / 100;
            $discount = round($discount);
        } elseif ($tierPrice->getDiscountAmount()) {
            $discount = $tierPrice->getDiscountAmount();
        } else {
            $discount = 0;
        }

        return $discount;
    }

    private function setTierPriceIndex($customerId, $productId, $finalPrice, $priority)
    {
        return [
                'customer_id' => $customerId,
                'product_id' => $productId,
                'priority' => $priority,
                'final_price' => $finalPrice
            ];
    }

    public function getIdProduct($sku)
    {
        $connection = $this->getConnection();
        $condition = $this->getConnection()
                ->prepareSqlCondition('sku', ['eq' => $sku]);
        $select = $connection->select()
            ->from(
                'catalog_product_entity',
                '*'
            )->where($condition);
        $product = $connection->fetchRow($select);
        $id = (isset($product['entity_id'])) ? $product['entity_id'] : null;

        return $id;
    }

    public function getSkuProduct($id)
    {
        $connection = $this->getConnection();
        $condition = $this->getConnection()
                ->prepareSqlCondition('entity_id', ['eq' => $id]);
        $select = $connection->select()
            ->from(
                'catalog_product_entity',
                '*'
            )->where($condition);
        $product = $connection->fetchRow($select);
        $sku = (isset($product['sku'])) ? $product['sku'] : null;

        return $sku;
    }

    private function saveDataToIndex($data)
    {
        $this->insertRowsToTable($data);

        return $this;
    }

    private function insertRowsToTable($rowsData)
    {
        $table = $this->getTable('icube_tier_price_idx');

        if (count($rowsData)) {
            $this->getConnection()->insertMultiple(
                $table,
                $rowsData
            );
        }
        return $this;
    }

    private function removeRowsFromTable($field, $data)
    {
        $connection = $this->getConnection();
        $connection->delete(
            'icube_tier_price_idx',
            [$connection->prepareSqlCondition($field, ['in' => $data])]
        );
    }
}
