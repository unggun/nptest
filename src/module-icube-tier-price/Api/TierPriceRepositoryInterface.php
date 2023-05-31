<?php

declare(strict_types=1);

namespace Icube\TierPrice\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface TierPriceRepositoryInterface
{
    /**
     * Save tier price
     *
     * @param \Icube\TierPrice\Model\TierPrice $tierPrice
     * @return \Icube\TierPrice\Model\TierPrice
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Icube\TierPrice\Model\TierPrice $tierPrice
    );

    /**
     * Retrieve tier price
     *
     * @param int $tierDiscountId
     * @return \Icube\TierPrice\Model\TierPrice
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($tierDiscountId);

    /**
     * Retrieve tier price matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete tier price
     *
     * @param \Icube\TierPrice\Model\TierPrice $tierPrice
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Icube\TierPrice\Model\TierPrice $tierPrice
    );

    /**
     * Delete tier price by ID
     *
     * @param int $tierDiscountId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($tierDiscountId);
}
