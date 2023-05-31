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

use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Option\Collection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean as BooleanSource;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\Store;

/**
 * Class DependentAttribute
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class DependentOption implements OptionSourceInterface
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
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var BooleanSource
     */
    private $booleanSource;

    /**
     * @var array
     */
    private $options;

    /**
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param AttributeRepositoryInterface $attributeRepository
     * @param BooleanSource $booleanSource
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        AttributeRepositoryInterface $attributeRepository,
        BooleanSource $booleanSource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
        $this->attributeRepository = $attributeRepository;
        $this->booleanSource = $booleanSource;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $attributeId = $this->request->getParam(AttributeInterface::ATTRIBUTE_ID, false);
        $options = [];

        if ($this->options === null && $attributeId) {
            $attribute = $this->attributeRepository->getById($attributeId);

            if ($attribute->getFrontendInput() == InputType::BOOL) {
                $options = $this->booleanSource->getAllOptions();
            } else {
                /** @var Collection $collection */
                $collection = $this->collectionFactory->create();
                $collection
                    ->setAttributeFilter($attributeId)
                    ->setPositionOrder();

                foreach ($collection as $option) {
                    $optionId = $option->getOptionId();
                    foreach ($option->getStoreLabels() as $storeLabel) {
                        if ($storeLabel['store_id'] == Store::DEFAULT_STORE_ID) {
                            $options[] = [
                                'value' => $optionId,
                                'label' => $storeLabel['label']
                            ];
                        }
                    }
                }
            }
        }
        $this->options = $options;

        return $this->options;
    }
}
