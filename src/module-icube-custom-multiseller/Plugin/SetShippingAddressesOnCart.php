<?php

namespace Icube\CustomMultiseller\Plugin;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class SetShippingAddressesOnCart
{
    public function aroundResolve($subject, callable $proceed, ...$args)
    {
        try {
            $proceed(...$args);
        } catch (\Throwable $th) {
            throw new GraphQlInputException(__($th->getMessage()));
        }
    }
}
