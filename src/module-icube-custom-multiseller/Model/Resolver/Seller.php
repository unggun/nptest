<?php
declare(strict_types=1);

namespace Icube\CustomMultiseller\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Override \Swiftoms\Multiseller\Model\Resolver\Seller
 */
class Seller implements ResolverInterface
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->resource = $resource;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        ## get Detail Info Product when call from query Product
        $product = $this->productRepository->getById($value["entity_id"]);
        $data = [
          "seller_id" => null,
          "seller_name" => null,
          "seller_city" => null,
          "seller_type" => null,
          "seller_sla_delivery" => null,
        ];
        if (!empty($value["seller_id"]) || $product->getSellerId()) {
            if (!empty($value["seller_id"])) {
                $seller_id = $value["seller_id"];
            } else {
                $seller_id = $product->getSellerId();
            }
            $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
            $themeTable = $this->resource->getTableName('icube_multiseller');
            $joinTable = $this->resource->getTableName('icube_sellerconfig');
            $sql = sprintf("SELECT im.*,is2.value model,is3.value sla FROM %s im
                LEFT JOIN %s is2 ON is2.seller_id = im.id AND is2.type = 'model'
                LEFT JOIN %s is3 ON is3.seller_id = im.id AND is3.type = 'sla_delivery'
                WHERE im.id = :id", $themeTable, $joinTable, $joinTable);
            $result = $connection->fetchAll($sql, [':id' => $seller_id]);

            if ($result) {
                $seller_cities = explode(",", $result[0]["city"]);
                $seller_city = $seller_cities[0];

                $data["seller_id"] = $seller_id;
                $data["seller_name"] = $result[0]["name"];
                $data["seller_city"] = $seller_city;
                $data["seller_type"] = $result[0]['model'] ?? '';
                $data["seller_sla_delivery"] = $result[0]['sla'] ?? '';
            }
        }
        return $data;
    }
}
