<?php
declare(strict_types=1);

namespace Icube\CatalogRestrictions\Model;

use Icube\CatalogRestrictions\Api\Data\SellerConfigInterface;
use Magento\Framework\Model\AbstractModel;

class SellerConfig extends AbstractModel implements SellerConfigInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Icube\CatalogRestrictions\Model\ResourceModel\SellerConfig::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($entityId)
    {
        return $this->setData(self::ENITTY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getSellerId()
    {
        return $this->getData(self::SELLER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSellerId($sellerId)
    {
        return $this->setData(self::SELLER_ID, $sellerId);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setValue($value)
    {
        return $this->setData(self::VALUE, $value);
    }
}
