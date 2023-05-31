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
namespace Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Option\Collection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Option\CollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form;

/**
 * Class Options
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier
 */
class Options implements ModifierInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory
    ) {
        $this->arrayManager = $arrayManager;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $options = $collection
            ->setAttributeFilter($data[AttributeInterface::ATTRIBUTE_ID])
            ->setPositionOrder()
            ->getItems();

        $prepared = [];
        /** @var Option $option */
        foreach ($options as $option) {
            $optionId = $option->getData('option_id');
            $prepared[$optionId] = [
                'option_id' => $optionId,
                'sort_order' => $option->getData('sort_order'),
                'store_labels' => []
            ];
            foreach ((array)$option->getData('store_labels') as $labelData) {
                $storeId = $labelData['store_id'];
                $prepared[$optionId]['store_labels'][$storeId] = $labelData['label'];
                if ($labelData['option_id'] == $data['default_value']) {
                    $prepared[$optionId]['is_default'] = '1';
                }
            }
        }
        $data[AttributeInterface::OPTIONS] = array_values($prepared);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $optionsPath = 'labels_and_options/children/options/children/record/children';
        if (!$this->arrayManager->findPath($optionsPath, $meta)) {
            $meta = $this->arrayManager->set($optionsPath, $meta, []);
        }
        $meta = $this->arrayManager->merge(
            $optionsPath,
            $meta,
            $this->getStoreLabelsElements()
        );
        return $meta;
    }

    /**
     * Retrieve store labels elements
     *
     * @return array
     */
    private function getStoreLabelsElements()
    {
        $children = [];
        $stores = $this->storeManager->getStores(true);
        ksort($stores);

        /** @var StoreInterface $store */
        foreach ($stores as $store) {
            $children['store_label_' . $store->getId()] =
                $this->getFieldConfig($store->getId(), $store->getName());
        }

        $children = array_merge(
            $children,
            [
                'action_delete' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Form\Element\ActionDelete::NAME,
                                'dataType' => Form\Element\DataType\Text::NAME,
                                'fit' => true
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $children;
    }

    /**
     * Retrieve field config
     *
     * @param int $value
     * @param string $label
     * @return array
     */
    private function getFieldConfig($value, $label)
    {
        $validation = (int)$value ? [] : ['validation' => ['required-entry' => true]];

        return [
            'attributes' => [
                'name' => 'store_label_' . $value,
                'class' => Form\Field::class
            ],
            'arguments' => [
                'data' => [
                    'config' => array_merge(
                        [
                            'componentType' => Form\Field::NAME,
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'dataScope' => 'store_labels.' . $value,
                            'formElement' => Form\Element\Input::NAME,
                            'source' => 'attribute',
                            'label' => $label
                        ],
                        $validation
                    )
                ],
            ],
        ];
    }
}
