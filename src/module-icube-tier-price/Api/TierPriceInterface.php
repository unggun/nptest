<?php

namespace Icube\TierPrice\Api;

interface TierPriceInterface
{
    /**
     * @param mixed $data
     * @return mixed
     */
    
    public function setData($data);

    /**
     * @param string $vendor_code
     * @param string $sku
     * @return mixed
     */
    
    public function get($vendor_code, $sku);

    /**
     * @param mixed $tier_discount_id
     * @return mixed
     */
    
    public function delete($tier_discount_id);
}
