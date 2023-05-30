<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\CustomCustomer\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * BusinessTypeList resolver
 */
class BusinessTypeList implements ResolverInterface
{
    protected $eavConfig;

    /**
     * CustomCustomer constructor.
     *
     * @param Config $eavConfig
     */
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig
    ){
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
        $attributeCode = "business_type";
        $attribute = $this->eavConfig->getAttribute('customer', $attributeCode);
        $options = $attribute->getSource()->getAllOptions();
        $result = [];
        foreach ($options as $option) {
            if ($option['value'] > 0) {
                $result[] = [
                    "code" => $option['value'],
                    "jenis_usaha" => $option['label']
                ];
            }
        }

        return $result;
    }
}