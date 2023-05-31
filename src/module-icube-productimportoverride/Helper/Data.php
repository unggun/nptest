<?php

namespace Icube\ProductImportOverride\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Icube\VoltwigCore\Model\Profile;
use Magento\Framework\App\ResourceConnection;


class Data extends \Swiftoms\GeneralImport\Helper\Data
{

    public function insertBulkData($data)
    {
        foreach($data as $key_data => $item_data){
            $keys = array_keys($data[$key_data]);
            $set_replace = implode(" ",$keys);
            $replace = str_replace("type_id","product.type",$set_replace);
            $back_replace = explode(" ",$replace);
            $value = array_values($data[$key_data]);
            $data[$key_data] = array_combine($back_replace,$value);
            
            if($data[$key_data]['product.type'] == 'configurable'){
                foreach($data as $key_data_new => $item_data_new){
                    if($data[$key_data]['linked_sku'][0] == $data[$key_data_new]['sku']){
                        $result = array_merge($data[$key_data_new], $data[$key_data]);
                        $result_diffs = array_diff_key($result,$data[$key_data]);
                        foreach($result_diffs as $result_diff_key => $result_diff){
                            $data[$key_data][$result_diff_key] = '';
                        }
                    }
                }
            }
        }

        $tableName = $this->resource->getTableName('oms_import_product_temp');
        $filepath = 'voltwigcore/import/products.csv';
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        try {
            $bulk = [
                'data' => json_encode($data)
            ];
            $this->connection->insert($tableName, $bulk);
        } catch (\Exception $e) {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('oms_import_product_temp');
            $query = "TRUNCATE TABLE`" . $tableName . "` ";
            $connection->query($query);
            return $e->getMessage();
        }
        $query = "Select * FROM " . $tableName;
        $result = $this->connection->fetchAll($query);

        if(count($result) > 0){
            $product = json_decode($result[0]['data'],true);

            // add required field
            foreach ($product as $key => $value){
                $product[$key]['product.attribute_set'] = 'Default';
                $product[$key]['weltpixel_exclude_from_sitemap'] = 'No';
                $product[$key]['content_constructor_content'] = 'No';
                $product[$key]['layout_update_xml_backup'] = 'No Update';

                //oveerride to check if sku is not exist, if exist, no need to update stock.qty
                $product[$key]['stock.qty'] = $this->isSkuNotExist($product[$key]['sku']);
                
                $product[$key]['category.path'] = implode(";",$product[$key]['url_path_extra']);
                if (isset($product[$key]['variant_attributes'])) {
                    foreach ($product[$key]['variant_attributes'][0] as $attr => $attrValue) {
                        $product[$key]['variant_attributes.'.$attr] = $attrValue;
                    }
                }
            }

            $var = [];
            $header = [];

            // remove unused array field
            foreach ($product as $key => $values){
                if (array_key_exists('url_path_extra', $values) || array_key_exists('images',$values) || array_key_exists('attribute_code_configurable',$values) || array_key_exists('linked_sku',$values) || array_key_exists('variant_attributes',$values)) {
                    $remove = ['url_path_extra','images','attribute_code_configurable','linked_sku','variant_attributes'];
                    $arr = array_diff_key($values, array_flip($remove));
                    $header[] = $arr;
                    $var[] = array_values($arr);

                }else{
                    $header[] = $values;
                    $var[] = array_values($values);

                }
            }
            $headerCsv = array_keys(call_user_func_array('array_merge', $header));
            sort($headerCsv);
            $varContent = [];
            foreach ($header as $head) {
                $sorted = $head;
                if (array_diff($headerCsv, array_keys($head))) {
                    foreach ($headerCsv as $value) {
                        if (isset($head[$value])) {
                            $sorted[$value] = $head[$value]; 
                        } else {
                            $sorted[$value] = '';
                        }
                    }
                }
                ksort($sorted);
                $varContent[] = array_values($sorted);
            }
            
            $header = array_keys($header[0]);
            $header = $headerCsv;
            $stream->writeCsv($header);

            $content = [];

            foreach ($varContent as $k => $v){
                $content = [];
                array_walk_recursive($v, function($item) use (&$content) {
                    $content[] = $item;
                });
                $stream->writeCsv($content);
            }

            return "ok";
        }
    }

    private function isSkuNotExist($sku)
    {
         $sql = "SELECT csi.qty AS qty FROM catalog_product_entity cpe RIGHT JOIN cataloginventory_stock_status csi ON cpe.`entity_id` = csi.`product_id` WHERE cpe.`sku` = ?";
        $productId = $this->connection->fetchRow($sql, [$sku]);
        
        if (!empty($productId)) {            
            return $productId['qty'];
        }
        return 0;
    }
}