<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\CustomProduct\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Fetches the Wishlist Items data according to the GraphQL schema
 */
class Uom implements ResolverInterface
{
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
        if (!isset($value['model'])) {
            throw new LocalizedException(__('Missing key "model" in Wishlist value data'));
        }

        $unitofMeasurement = "";
        $value['model'];

        if (null != $value['model']->getCustomAttribute('uom')) {
            $unitofMeasurement = $value['model']->getAttributeText('uom');
        }

        return $unitofMeasurement;
    }
}