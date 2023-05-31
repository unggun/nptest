<?php

declare(strict_types=1);

namespace Icube\OverrideAdvancerate\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Icube\OverrideAdvancerate\Model\Advancerate;
use Ced\Advancerate\Model\ResourceModel\Carrier\Advancerate as AdvancerateResource;

/**
 * Add customer user resolver
 */
class SaveAdvanceRate implements ResolverInterface
{
	/**
     * @var Advancerate
     */
    private $advancerate;

    /**
     * @var AdvancerateResource
     */
    private $advancerateResource;

    /**
     * AddCustomerUser constructor.
     *
     * @param Advancerate $advancerate
     * 
     */
    public function __construct(
        Advancerate $advancerate,
        AdvancerateResource $advancerateResource
    ) {
        $this->advancerate = $advancerate;
        $this->advancerateResource = $advancerateResource;
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

        try {
            $this->validate($args);
            $input = $args['input'];

            if (!$this->isNewRecord($args)) {
                $this->advancerateResource->load($this->advancerate, $input['id'], 'id');
                if (empty($this->advancerate->getData())) {
                    throw new GraphQlInputException(__('Advance rate id: '.$input['id'].' not found.'));
                }
            }
            $this->validateRange($args);

            $this->advancerate->addData($input);
            $websiteId = $context->getExtensionAttributes()->getStore()->getWebsiteId();
            $this->advancerate->setData('website_id', $websiteId);
            $this->advancerateResource->save($this->advancerate);

		} catch (\Exception $e) {
			throw new GraphQlInputException(__($e->getMessage()));
		}


        return $this->advancerate->getData();
	}

    private function validate($args)
    {
        if ($this->isNewRecord($args)) {
            if (!isset($args['input']['vendor_id']) || empty($args['input']['vendor_id'])) {   
                throw new GraphQlInputException(__("vendor_id is required"));
            }
    
            if (!isset($args['input']['dest_country_id']) || empty($args['input']['dest_country_id'])) {   
                throw new GraphQlInputException(__("dest_country_id is required"));
            }
    
            if (!isset($args['input']['dest_region_id']) || empty($args['input']['dest_region_id'])) {   
                throw new GraphQlInputException(__("dest_region_id is required"));
            }
    
            if (!isset($args['input']['dest_zip']) || empty($args['input']['dest_zip'])) {   
                throw new GraphQlInputException(__("dest_zip is required"));
            }
    
            if (!isset($args['input']['weight_from']) || empty($args['input']['weight_from'])) {   
                throw new GraphQlInputException(__("weight_from is required"));
            }
    
            if (!isset($args['input']['weight_to']) || empty($args['input']['weight_to'])) {   
                throw new GraphQlInputException(__("weight_to is required"));
            }
    
            if (!isset($args['input']['price_from']) || empty($args['input']['price_from'])) {   
                throw new GraphQlInputException(__("price_from is required"));
            }
    
            if (!isset($args['input']['price_to']) || empty($args['input']['price_to'])) {   
                throw new GraphQlInputException(__("price_to is required"));
            }
    
            if (!isset($args['input']['qty_from']) || empty($args['input']['qty_from'])) {   
                throw new GraphQlInputException(__("qty_from is required"));
            }
    
            if (!isset($args['input']['qty_to']) || empty($args['input']['qty_to'])) {   
                throw new GraphQlInputException(__("qty_to is required"));
            }
    
            if (!isset($args['input']['price']) || empty($args['input']['price'])) {   
                throw new GraphQlInputException(__("price is required"));
            }
    
            if (!isset($args['input']['shipping_method']) || empty($args['input']['shipping_method'])) {   
                throw new GraphQlInputException(__("shipping_method is required"));
            }
    
            if (!isset($args['input']['shipping_label']) || empty($args['input']['shipping_label'])) {   
                throw new GraphQlInputException(__("shipping_label is required"));
            }

            if (!isset($args['input']['customer_group']) || empty($args['input']['customer_group'])) {   
                throw new GraphQlInputException(__("customer_group is required"));
            }
        }
        
    }

    private function validateRange($args)
    {
        $minWeight = $maxWeight = $minPrice = $maxPrice = $minQty = $maxQty = 0;
        if ($this->isNewRecord($args)) {
            $minWeight = $args['input']['weight_from'];
            $maxWeight = $args['input']['weight_to'];
            $minPrice = $args['input']['price_from'];
            $maxPrice = $args['input']['price_to'];
            $minQty = $args['input']['qty_from'];
            $maxQty = $args['input']['qty_to'];

        } else {

            $this->advancerateResource->load($this->advancerate, $args['input']['id'], 'id');
            if (isset($args['input']['weight_from']) || isset($args['input']['weight_to'])) {
                $minWeight = $args['input']['weight_from'] ?? $this->advancerate->getWeightFrom();
                $maxWeight = $args['input']['weight_to'] ?? $this->advancerate->getWeightTo();
            }

            if (isset($args['input']['price_from']) || isset($args['input']['price_to'])) {
                $minPrice = $args['input']['price_from'] ?? $this->advancerate->getPriceFrom();
                $maxPrice = $args['input']['price_to'] ?? $this->advancerate->getPriceTo();
            }

            if (isset($args['input']['qty_from']) || isset($args['input']['qty_to'])) {
                $minQty = $args['input']['qty_from'] ?? $this->advancerate->getQtyFrom();
                $maxQty = $args['input']['qty_to'] ?? $this->advancerate->getQtyTo();
            }
        }

        if ($minWeight > $maxWeight) {
            throw new GraphQlInputException(__("weight_to should be bigger than weight_from"));
        }

        if ($minPrice > $maxPrice) {
            throw new GraphQlInputException(__("price_to should be bigger than price_from"));
        }

        if ($minQty > $maxQty) {
            throw new GraphQlInputException(__("qty_to should be bigger than qty_from"));
        }
    }

    private function isNewRecord($args)
    {
        return !isset($args['input']['id']);
    }
		
}
