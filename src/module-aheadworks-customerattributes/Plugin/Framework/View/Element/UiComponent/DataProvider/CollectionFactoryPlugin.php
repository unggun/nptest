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
namespace Aheadworks\CustomerAttributes\Plugin\Framework\View\Element\UiComponent\DataProvider;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Order\AttributeJoinProcessor;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;

/**
 * Class CollectionFactoryPlugin
 * @package Aheadworks\CustomerAttributes\Plugin\Framework\View\Element\UiComponent\DataProvider
 */
class CollectionFactoryPlugin
{
    /**
     * @var AttributeJoinProcessor
     */
    private $attributeJoinProcessor;

    /**
     * @param AttributeJoinProcessor $attributeJoinProcessor
     */
    public function __construct(
        AttributeJoinProcessor $attributeJoinProcessor
    ) {
        $this->attributeJoinProcessor = $attributeJoinProcessor;
    }

    /**
     * Trigger collection provider name
     */
    const TRIGGER_NAME = 'sales_order_grid_data_source';

    /**
     * Join order attribute values
     *
     * @param CollectionFactory $subject
     * @param Collection $collection
     * @param string $requestName
     * @return Collection
     */
    public function afterGetReport(CollectionFactory $subject, $collection, $requestName)
    {
        if ($requestName === self::TRIGGER_NAME) {
            $this->attributeJoinProcessor->joinAttributeValues($collection);
        }

        return $collection;
    }
}
