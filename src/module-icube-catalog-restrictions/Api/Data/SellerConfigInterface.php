<?php
declare(strict_types=1);

namespace Icube\CatalogRestrictions\Api\Data;

interface SellerConfigInterface
{
    const GROUP_KEY = 'group';
    const ZIPCODE_KEY = 'zipcode';
    const SELLER_ID = 'seller_id';
    const ENTITY_ID = 'entity_id';
    const VALUE = 'value';
    const TYPE = 'type';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Icube\CatalogRestrictions\SellerConfig\Api\Data\SellerConfigInterface
     */
    public function setId($entityId);

    /**
     * Get seller_id
     * @return string|null
     */
    public function getSellerId();

    /**
     * Set seller_id
     * @param string $sellerId
     * @return \Icube\CatalogRestrictions\SellerConfig\Api\Data\SellerConfigInterface
     */
    public function setSellerId($sellerId);

    /**
     * Get type
     * @return string|null
     */
    public function getType();

    /**
     * Set type
     * @param string $type
     * @return \Icube\CatalogRestrictions\SellerConfig\Api\Data\SellerConfigInterface
     */
    public function setType($type);

    /**
     * Get value
     * @return string|null
     */
    public function getValue();

    /**
     * Set value
     * @param string $value
     * @return \Icube\CatalogRestrictions\SellerConfig\Api\Data\SellerConfigInterface
     */
    public function setValue($value);
}
