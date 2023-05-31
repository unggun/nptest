<?php

declare(strict_types=1);

namespace Icube\OverrideAdvancerate\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Ced\Advancerate\Model\ResourceModel\Carrier\Advancerate\Collection as AdvancerateCollection;

/**
 * Add customer user resolver
 */
class GetAdvanceRate implements ResolverInterface
{
	/**
     * @var AdvancerateCollection
     */
    private $advancerateCollection;

    /**
     * AddCustomerUser constructor.
     *
     * @param AdvancerateCollection $customerRepository
     * 
     */
    public function __construct(
        AdvancerateCollection $advancerateCollection
    ) {
        $this->advancerateCollection = $advancerateCollection;
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
        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        
        if (isset($args['filter']['vendor_id']) && !empty($args['filter']['vendor_id']) && isset($args['filter']['wilayah']) && !empty($args['filter']['wilayah'])) {   
            $vendorId = $args['filter']['vendor_id'];
            $wilayah = $args['filter']['wilayah'];
            $collection = $this->advancerateCollection
                        ->addFieldToFilter('vendor_id', ['vendor_id' => $vendorId])
                        ->addFieldToFilter('wilayah', ['wilayah' => $wilayah]);

        } elseif (isset($args['filter']['vendor_id']) && !empty($args['filter']['vendor_id'])) {
            $vendorId = $args['filter']['vendor_id'];
            $collection = $this->advancerateCollection
                        ->addFieldToFilter('vendor_id', ['vendor_id' => $vendorId]);
        } elseif (isset($args['filter']['wilayah']) && !empty($args['filter']['wilayah'])) {
            $wilayah = $args['filter']['wilayah'];
            $collection = $this->advancerateCollection
                        ->addFieldToFilter('wilayah', ['wilayah' => $wilayah]);
        } else {
            throw new GraphQlInputException(__('Please set vendor_id or wilayah'));
        }

        $collection->setPageSize($args['pageSize']);
        $collection->setCurPage($args['currentPage']);

        $totalPages = 0;
        if ($collection->getSize() > 0) {
            $totalPages = ceil($collection->getSize() / $collection->getPageSize());
        }

        return [
            'items' => $collection->getData(),
            'total_count' => $collection->getSize(),
            'page_info' => [
                'total_pages' => $totalPages,
                'page_size' => $collection->getPageSize(),
                'current_page' => $collection->getCurPage(),
            ]
        ];
	}
		
}
