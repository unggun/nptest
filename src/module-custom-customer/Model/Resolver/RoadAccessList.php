<?php
namespace Icube\CustomCustomer\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Eav\Model\Config;

/**
 * RoadAccesssList resolver
 */
class RoadAccessList implements ResolverInterface
{
    protected $eavConfig;

    /**
     * CustomCustomer constructor.
     *
     * @param Config $eavConfig
     */
    public function __construct(
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
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
        $attributeCode = "road_access";
        $attribute = $this->eavConfig->getAttribute('customer_address', $attributeCode);
        $options = $attribute->getSource()->getAllOptions();
        $result = [];
        foreach ($options as $option) {
            if ($option['value'] > 0) {
                $result[] = [
                    "code" => $option['value'],
                    "value" => $option['label']
                ];
            }
        }

        return $result;
    }
}
