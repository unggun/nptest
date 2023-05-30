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
namespace Aheadworks\CustomerAttributes\Model\Export;

use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;

/**
 * Class OptionsLoader
 * @package Aheadworks\CustomerAttributes\Model\Export
 */
class OptionsLoader
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var array
     */
    private $options;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Retrieve export attributes options
     *
     * @return array
     */
    public function getExportAttributesOptions()
    {
        if ($this->options === null) {
            $options = [];

            /** @var AttributeInterface|Attribute $attribute */
            foreach ($this->getAttributes() as $attribute) {
                try {
                    $prepared = [];
                    $allOptions = $attribute->getSource()->getAllOptions(false);
                    foreach ($allOptions as $option) {
                        $prepared[$option['value']] = $option['label'];
                    }
                    $options[$attribute->getAttributeCode()] = $prepared;
                    $options[AttributeResource::COLUMN_PREFIX . $attribute->getAttributeCode()] = $prepared;
                } catch (LocalizedException $e) {
                    continue;
                }
            }
            $this->options = $options;
        }

        return $this->options;
    }

    /**
     * Retrieve attributes
     *
     * @return AttributeInterface[]
     */
    private function getAttributes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                AttributeInterface::FRONTEND_INPUT,
                [InputType::BOOL, InputType::MULTISELECT, InputType::DROPDOWN],
                'in'
            )
            ->addFilter(AttributeInterface::IS_USER_DEFINED, 1)
            ->create();

        return (array)$this->attributeRepository->getList($searchCriteria)->getItems();
    }
}
