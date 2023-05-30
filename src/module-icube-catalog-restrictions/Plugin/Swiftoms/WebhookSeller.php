<?php
declare(strict_types=1);

namespace Icube\CatalogRestrictions\Plugin\Swiftoms;

use Icube\CatalogRestrictions\Api\Data\SellerConfigInterface;
use Icube\CatalogRestrictions\Api\Data\SellerConfigInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class WebhookSeller
{
    protected ResourceConnection $resource;

    protected SellerConfigInterfaceFactory $sellerConfigFactory;

    protected LoggerInterface $logger;

    public function __construct(
        ResourceConnection $resource,
        SellerConfigInterfaceFactory $sellerConfigFactory,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->sellerConfigFactory = $sellerConfigFactory;
        $this->logger = $logger;
    }

    public function afterPostSeller(
        \Swiftoms\GeneralImport\Model\Webhook $subject,
        $result,
        $data
    ) {
        if (isset($data['additional_info']) && !empty($data['additional_info'])) {
            $sellerId = $data['id'];
            $sellerInfo = $data['additional_info'];
            if (!is_array($sellerInfo)) {
                $sellerInfo = json_decode($sellerInfo, true);
            }
            $insertData = [];
            foreach ($sellerInfo as $info) {
                $sellerId = $info['vendor_id'];
                $type = $info['attribute_code'];
                if (is_array($info['value'])) {
                    foreach ($info['value'] as $value) {
                        $insertData[] = [
                            'seller_id' => $sellerId,
                            'type' => $type,
                            'value' => $value
                        ];
                    }
                } else {
                    $insertData[] = [
                        'seller_id' => $sellerId,
                        'type' => $type,
                        'value' => $info['value']
                    ];
                }
            }
            try {
                $configs = $this->sellerConfigFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('seller_id', $sellerId);
                $sellerConfigTable = $this->resource->getTableName('icube_sellerconfig');
                if ($configs->count() > 0) {
                    // remove old configs
                    $this->resource->getConnection()->delete(
                        $sellerConfigTable,
                        ['seller_id = ?' => $sellerId]
                    );
                }
                $this->resource->getConnection()->insertMultiple($sellerConfigTable, $insertData);
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }
        return $result;
    }
}
