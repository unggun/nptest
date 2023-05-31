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
namespace Aheadworks\CustomerAttributes\Model\Source\Attribute;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class DependentAttribute
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class DependentAttribute implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $options;

    /**
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RequestInterface $request
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            /** @var Collection $collection */
            $collection = $this->collectionFactory->create($this->request->getParam('type'));
            $collection
                ->addSystemHiddenFilter()
                ->addExcludeHiddenFrontendFilter()
                ->setOrder(AttributeInterface::ATTRIBUTE_ID);
            if ($attributeId = $this->request->getParam(AttributeInterface::ATTRIBUTE_ID, false)) {
                $collection->addFieldToFilter(AttributeInterface::ATTRIBUTE_ID, ['neq' => $attributeId]);
            }
            $this->options = $collection->toOptionsArray(
                AttributeInterface::ATTRIBUTE_ID,
                AttributeInterface::FRONTEND_LABEL
            );
        }

        return $this->options;
    }
}
