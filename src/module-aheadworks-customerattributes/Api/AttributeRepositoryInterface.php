<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Api;

/**
 * Interface AttributeRepositoryInterface
 * @package Aheadworks\CustomerAttributes\Api
 */
interface AttributeRepositoryInterface
{
    /**
     * Retrieve specific attribute by id
     *
     * @param int $attributeId
     * @return \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($attributeId);
    
    /**
     * Retrieve specific attribute by code
     *
     * @param string $attributeCode
     * @return \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByCode($attributeCode);

    /**
     * Create attribute data
     *
     * @param \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface $attribute
     * @return \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Aheadworks\CustomerAttributes\Api\Data\AttributeInterface $attribute);

    /**
     * Delete Attribute
     *
     * @param \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface $attribute
     * @return bool True if the entity was deleted
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Aheadworks\CustomerAttributes\Api\Data\AttributeInterface $attribute);

    /**
     * Delete Attribute By Id
     *
     * @param int $attributeId
     * @return bool True if the entity was deleted
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($attributeId);
    
    /**
     * Retrieve all attributes for entity type
     *
     * @param string $entityTypeCode
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Aheadworks\CustomerAttributes\Api\Data\AttributeSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
