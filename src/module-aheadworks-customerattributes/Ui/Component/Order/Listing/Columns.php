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
namespace Aheadworks\CustomerAttributes\Ui\Component\Order\Listing;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns as UiColumns;
use Aheadworks\CustomerAttributes\Model\Attribute\Provider as AttributeProvider;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;

/**
 * Class Columns
 * @package Aheadworks\CustomerAttributes\Ui\Component\Order\Listing
 */
class Columns extends UiColumns
{
    /**
     * @var int
     */
    private $maxSortOrder;

    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * @var UiComponentFactory
     */
    private $componentFactory;

    /**
     * @var array
     */
    private $filterMap = [
        'select' => 'select',
        'boolean' => 'select',
        'multiselect' => 'select',
        'date' => 'dateRange',
    ];

    /**
     * @var array
     */
    private $componentMap = [
        'text' => 'Magento_Ui/js/grid/columns/column',
        'boolean' => 'Magento_Ui/js/grid/columns/select',
        'select' => 'Magento_Ui/js/grid/columns/select',
        'multiselect' => 'Magento_Ui/js/grid/columns/select',
        'date' => 'Magento_Ui/js/grid/columns/date',
    ];

    /**
     * @var array
     */
    private $dataTypeMap = [
        'text' => 'text',
        'boolean' => 'select',
        'select' => 'select',
        'multiselect' => 'select',
        'date' => 'date',
    ];

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $componentFactory
     * @param AttributeProvider $attributeProvider
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $componentFactory,
        AttributeProvider $attributeProvider,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->componentFactory = $componentFactory;
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * Get max sort order
     *
     * @return int
     */
    private function getMaxSortOrder()
    {
        $max = 0;
        foreach ($this->components as $component) {
            $sortOrder = $component->getData('config/sortOrder');
            $max = max($sortOrder, $max);
        }
        return ++$max;
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    public function prepare()
    {
        $this->maxSortOrder = $this->getMaxSortOrder();
        foreach ($this->attributeProvider->getOrderGridAttributes() as $attribute) {
            if ($attribute->getUsedInOrderGrid()) {
                $this->addColumn($attribute);
            }
        }
        parent::prepare();
    }

    /**
     * Add column
     *
     * @param AttributeInterface $attribute
     * @return void
     * @throws LocalizedException
     */
    public function addColumn($attribute)
    {
        $name = AttributeResource::COLUMN_PREFIX . $attribute->getAttributeCode();
        $column = $this->componentFactory->create($name, 'column', $this->getColumnArguments($attribute));
        $column->prepare();
        $this->addComponent($name, $column);
    }

    /**
     * Get column arguments
     *
     * @param AttributeInterface|Attribute $attribute
     * @return array
     * @throws LocalizedException
     */
    private function getColumnArguments($attribute)
    {
        $frontendInput = $attribute->getFrontendInput();
        $arguments = [
            'data' => [
                'config' => [
                    'sortOrder' => ++$this->maxSortOrder,
                    'filter' => isset($this->filterMap[$frontendInput]) ? $this->filterMap[$frontendInput] : 'text',
                    'label' => __($attribute->getFrontendLabel()),
                    'dataType' => isset($this->dataTypeMap[$frontendInput])
                        ? $this->dataTypeMap[$frontendInput]
                        : 'text',
                    'component' => isset($this->componentMap[$frontendInput])
                        ? $this->componentMap[$frontendInput]
                        : $this->componentMap['text']
                ],
            ],
            'context' => $this->getContext(),
        ];

        if ($frontendInput === InputType::DATE) {
            $arguments['data']['config']['dateFormat'] = 'MMM d, y';
        }
        if ($attribute->usesSource()) {
            $arguments['data']['options'] = $attribute->getSource()->getAllOptions();
        }

        return $arguments;
    }
}
