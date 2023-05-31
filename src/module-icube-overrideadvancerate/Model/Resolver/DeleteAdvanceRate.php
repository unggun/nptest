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
class DeleteAdvanceRate implements ResolverInterface
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
        if (!isset($args['id']) || empty($args['id'])) {   
            throw new GraphQlInputException(__("id is required"));
        }

        try {
            $this->advancerateResource->load($this->advancerate, $args['id'], 'id');
            if (empty($this->advancerate->getData())) {
                throw new GraphQlInputException(__('Advance rate id: '.$args['id'].' not found.'));
            }

            $this->advancerateResource->delete($this->advancerate);

		} catch (\Exception $e) {
			throw new GraphQlInputException(__($e->getMessage()));
		}


        return ['message' => 'Delete advance rate successfull.'];
	}
		
}
