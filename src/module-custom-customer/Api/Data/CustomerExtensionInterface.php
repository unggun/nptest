<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Icube\CustomCustomer\Api\Data;

/**
 * Emphasis Solutions info interface.
 */
interface CustomerExtensionInterface
{
    const BUSINESS_TYPE = 'business_type';
    const OTHER_BUSINESS_TYPE = 'other_business_type';

    /**
     * Return Business Type.
     *
     * @return string|null
     */
    public function getBusinessType();

    /**
     * Set Business Type.
     *
     * @param string $businessType
     * @return $this
     */
    public function setBusinessType($businessType);

    /**
     * Return Other Business Type.
     *
     * @return string|null
     */
    public function getOtherBusinessType();

    /**
     * Set Other Business Type.
     *
     * @param string $otherBusinessType
     * @return $this
     */
    public function setOtherBusinessType($otherBusinessType);
}
