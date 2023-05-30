<?php

namespace Icube\ProductImportOverride\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;

class Inventory extends \Swiftoms\InventoryUpdate\Helper\Inventory
{
    public function bulkUpdateStock($output)
    {
        $sql = "SELECT id FROM swift_webhook WHERE event = 'inventory.update'  AND status = 'processing' ORDER BY created_at ASC LIMIT 1";
        $isExist = $this->connection->fetchOne($sql);
        if (!empty($isExist)) {
            $output->writeln('Inventory update is running..');
            return false;
        }

        $sql = "SELECT id, datajson FROM swift_webhook WHERE event = 'inventory.update' AND status = 'pending' ORDER BY created_at ASC LIMIT 10";
        $results = $this->connection->fetchAll($sql);

        if (!$results) {
            $output->writeln('No data to process..');
            return false;
        }


        foreach ($results as $result) {
            $webhookId = isset($result['id']) ? $result['id'] : '';
            if (!$webhookId) continue;

            $this->updateWebhookStatus($webhookId, 'processing');

            $data = isset($result['datajson']) ? json_decode($result['datajson'], true) : [];
            if ($data) {
                foreach ($data as $channelStock) {
                    $channelCode = isset($channelStock['channel_code']) ? $channelStock['channel_code'] : '';
                    if ($channelCode != $this->getChannelCode()) continue;

                    $items = isset($channelStock['items']) ? $channelStock['items'] : [];
                    if ($items) {
                        $vsCode = 'default';
                        $sourceItems = [];

                        try {
                            foreach ($items as $item) {
                                if (!$this->isSkuExist($item['sku'])) {
                                    $output->writeln('Failed Process SKU Not Found : ' . $item['sku']);
                                    $this->writeLog("Error SKU Not Found: " . $item['sku']);
                                    continue;
                                }

                                $qtyReserverd = $this->getQtyReserverd($item['sku'], $vsCode);
                                if (is_numeric($qtyReserverd)) {
                                    $output->writeln('On Process : ' . $item['sku']);

                                    $stockStatus = $item['qty'] > 0 ? 1 : 0;
                                    $sourceItem = $this->sourceItem->create();
                                    $sourceItem->setSourceCode($vsCode);
                                    $sourceItem->setSku($item['sku']);
                                    $sourceItem->setQuantity($item['qty'] + abs($qtyReserverd));
                                    $sourceItem->setStatus($stockStatus);
                                    $sourceItems[] = $sourceItem;

                                    $this->updateIsInStock($item['sku'], $stockStatus);
                                }
                            }


                            $this->itemSave->execute($sourceItems);

                            //add query to set stock_status = 1 in cataloginventory_stock_status where quantity > 0
                            $this->setStockStatus();
                        } catch (\Throwable $th) {
                            $output->writeln($th->getMessage());
                            $this->writeLog("Error : " . $th->getMessage() . "Data : " . json_encode($items));
                        }
                    }
                }
            }

            $this->updateInStock();
            $this->updateOutOfStock();
            $this->updateReserveOutOfStock();

            $this->deleteWebhook($webhookId);
        }

        $output->writeln('Finished!');
    }

    /**
     * Update is in stock status same as stock status
     * `cataloginventory_stock_item`.`is_in_stock` == `cataloginventory_stock_status`.`stock_status`
     *
     * @param String $sku
     * @param Integer $status 1 = in stock | 0 out of stock
     *
     * @return void
     */
    private function updateIsInStock($sku, $status)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $om->get('\Magento\Framework\App\State')->setAreaCode('adminhtml');
        $stockRegistry = $om->get('\Magento\CatalogInventory\Api\StockRegistryInterface');

        $stockItem = $stockRegistry->getStockItemBySku($sku);
        $stockItem->setIsInStock($status);
        $stockRegistry->updateStockItemBySku($sku, $stockItem);
    }

    private function updateInStock()
    {
        $sql = 'SELECT cpe.entity_id
            FROM catalog_product_entity cpe
            RIGHT JOIN cataloginventory_stock_item cpi ON cpe.entity_id = cpi.product_id
            RIGHT JOIN cataloginventory_stock_status cpse ON cpse.product_id = cpe.entity_id
            RIGHT JOIN catalog_product_super_link cpsl ON cpe.entity_id = cpsl.parent_id
            RIGHT JOIN cataloginventory_stock_status cps ON cps.product_id = cpsl.product_id
            WHERE cpe.type_id = "configurable" AND (cpi.is_in_stock !=1 OR cpse.stock_status !=1)
            GROUP BY cpe.entity_id
            HAVING sum(cps.qty)>0  ';
        $listEntity = $this->connection->fetchAll($sql);

        if (count($listEntity) < 1) {
            return 0;
        }

        $listEntityArray = array();
        foreach ($listEntity as $entity) {
            $listEntityArray[] = $entity['entity_id'];
        }

        $listEntityStr = implode(',', $listEntityArray);

        if (count($listEntityArray) < 1) {
            return 0;
        }

        $sql = 'UPDATE
                 cataloginventory_stock_item csi,
                 cataloginventory_stock_status css
                SET csi.is_in_stock = 1, css.stock_status= 1
                WHERE csi.product_id = css.product_id AND csi.product_id IN (' . $listEntityStr . ')';
        return $this->connection->query($sql);
    }

    /**
     * Update out of stock Configurable product when Configurable item is out of stock
     */
    private function updateOutOfStock()
    {
        $sql = 'SELECT cpe.entity_id
            FROM catalog_product_entity cpe
            RIGHT JOIN cataloginventory_stock_item cpi ON cpe.entity_id = cpi.product_id
            RIGHT JOIN cataloginventory_stock_status cpse ON cpse.product_id = cpe.entity_id
            RIGHT JOIN catalog_product_super_link cpsl ON cpe.entity_id = cpsl.parent_id
            RIGHT JOIN cataloginventory_stock_status cps ON cps.product_id = cpsl.product_id
            WHERE cpe.type_id = "configurable" AND (cpi.is_in_stock =1 OR cpse.stock_status =1)
            GROUP BY cpe.entity_id
            HAVING sum(cps.qty)=0';

        $listEntity = $this->connection->fetchAll($sql);

        if (count($listEntity) < 1) {
            return 0;
        }

        $listEntityArray = array();
        foreach ($listEntity as $entity) {
            $listEntityArray[] = $entity['entity_id'];
        }

        $listEntityStr = implode(',', $listEntityArray);

        if (count($listEntityArray) < 1) {
            return 0;
        }

        $sql = 'UPDATE cataloginventory_stock_item csi, cataloginventory_stock_status css
                SET csi.is_in_stock = 0, css.stock_status= 0
                WHERE csi.product_id = css.product_id AND csi.product_id IN (' . $listEntityStr . ')';

        $this->connection->query($sql);
    }

    /**
     * Force Update configurable product and simple product out of stock
     * Reference from table inventory_reservation
     *
     * When cataloginventory_stock_status.qty + sum(inventory_reservation.quantity) = 0
     */
    private function updateReserveOutOfStock()
    {
        $sql = 'SELECT cpe.entity_id, cpsl.parent_id, cps.qty AS stock, cpr.sku, sum(cpr.quantity) AS reserverd
            FROM catalog_product_entity cpe
            RIGHT JOIN inventory_reservation cpr ON cpe.sku = cpr.sku
            RIGHT JOIN catalog_product_super_link cpsl ON cpsl.product_id = cpe.entity_id
            LEFT JOIN cataloginventory_stock_status cps ON cps.product_id = cpe.entity_id
            WHERE cpr.quantity IS NOT NULL
            GROUP BY cpr.sku, cps.qty, cpsl.parent_id, cpe.entity_id
            ORDER BY (stock+reserverd) ASC';

        $listReserverd = $this->connection->fetchAll($sql);

        if (count($listReserverd) < 1) {
            return 0;
        }

        $listEntityArray = [];

        foreach ($listReserverd as $reserverd) {
            $parentId = $reserverd['parent_id'];
            /**
             * Check current item is zero stock
             */
            if (($reserverd["stock"] + $reserverd["reserverd"]) == 0) {
                $listEntityArray[] = $parentId;
                $listEntityArray[] = $reserverd['entity_id'];
            } else {
                /**
                 * Check if parent has multi child And it's not zero stock
                 */
                if (($key = array_search($parentId, $listEntityArray)) !== false) {
                    unset($listEntityArray[$key]);
                }
            }
        }

        $listEntityStr = implode(',', $listEntityArray);

        if (count($listEntityArray) < 1) {
            return 0;
        }

        $sql = 'UPDATE cataloginventory_stock_item csi, cataloginventory_stock_status css
                SET csi.is_in_stock = 0, css.stock_status= 0
                WHERE csi.product_id = css.product_id AND csi.product_id IN (' . $listEntityStr . ')';

        $this->connection->query($sql);
    }

    private function setStockStatus()
    {
        $sql = "UPDATE cataloginventory_stock_status SET stock_status = 1 WHERE qty > 0";
        return $this->connection->query($sql);
    }

    private function updateWebhookStatus($id, $status)
    {
        $sql = "UPDATE swift_webhook SET status = ? WHERE id = ?";
        return $this->connection->query($sql, [$status, $id]);
    }

    private function deleteWebhook($id)
    {
        $sql = "DELETE FROM swift_webhook WHERE id = ?";
        return $this->connection->query($sql, [$id]);
    }

    private function isSkuExist($sku)
    {
        $sql = "SELECT entity_id FROM catalog_product_entity WHERE sku = ?";
        $productId = $this->connection->fetchOne($sql, [$sku]);
        if (empty($productId)) {
            return false;
        }
        return true;
    }

    private function getStockId($vsCode)
    {
        $sql = "SELECT stock_id FROM inventory_source_stock_link WHERE source_code = ?";
        return $this->connection->fetchOne($sql, [$vsCode]);
    }

    private function getQtyReserverd($sku, $vsCode)
    {
        $stockId = $this->getStockId($vsCode);

        $sql = "SELECT COALESCE(SUM(ir.`quantity`), 0) AS qty_reserved FROM inventory_reservation ir WHERE ir.`sku` = ? AND ir.`stock_id` = ?";
        $result = $this->connection->fetchRow($sql, [$sku, $stockId]);

        return isset($result['qty_reserved']) ? $result['qty_reserved'] : 0;
    }
}
