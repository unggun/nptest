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
namespace Aheadworks\CustomerAttributes\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface AttributeSearchResultsInterface
 * @package Aheadworks\CustomerAttributes\Api\Data
 */
interface AttributeSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get attribute list
     *
     * @return \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface[]
     */
    public function getItems();

    /**
     * Set attribute list
     *
     * @param \Aheadworks\CustomerAttributes\Api\Data\AttributeInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
