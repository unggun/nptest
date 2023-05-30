<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\TierPrice\Model;

use Icube\TierPrice\Model\ResourceModel\TierPrice as TierPriceResourceModel;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class TierPrice extends AbstractModel implements IdentityInterface
{
    protected $_cacheTag = 'icube_tier_price';
    protected $_eventPrefix = 'icube_tier_price';
    
    public const CACHE_TAG = 'icube_tier_price';
    public const ENTITY_TAG = 'tier_discount_id';

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(TierPriceResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
    
    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
    
    /**
     * Get tier discount id
     *
     * @return int
     */
    public function getTierDiscountId()
    {
        return (int)$this->_getData('tier_discount_id');
    }

    /**
     * Get erp promo id
     *
     * @return string
     */
    public function getErpPromoId()
    {
        return $this->_getData('erp_promo_id');
    }

    /**
     * Get erp id
     *
     * @return string
     */
    public function getErpId()
    {
        return $this->_getData('erp_id');
    }

    /**
     * Get vendor code
     *
     * @return string
     */
    public function getVendorCode()
    {
        return $this->_getData('vendor_code');
    }

    /**
     * Get creator
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->_getData('creator');
    }

    /**
     * Get customer group id
     *
     * @return string
     */
    public function getCustomerGroupId()
    {
        return $this->_getData('customer_group_id');
    }

    /**
     * Get customer id
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->_getData('customer_id');
    }

    /**
     * Get product sku
     *
     * @return string
     */
    public function getProductSku()
    {
        return $this->_getData('product_sku');
    }

    /**
     * Get step qty
     *
     * @return int
     */
    public function getStepQty()
    {
        return (int)$this->_getData('step_qty');
    }

    /**
     * Get discount percentage
     *
     * @return float
     */
    public function getDiscountPercentage()
    {
        return (float)$this->_getData('discount_percentage');
    }

    /**
     * Get discount amount
     *
     * @return float
     */
    public function getDiscountAmount()
    {
        return (float)$this->_getData('discount_amount');
    }

    /**
     * Get start date
     *
     * @return string
     */
    public function getStartDate()
    {
        return $this->_getData('start_date');
    }

    /**
     * Get end date
     *
     * @return string
     */
    public function getEndDate()
    {
        return $this->_getData('end_date');
    }
}
