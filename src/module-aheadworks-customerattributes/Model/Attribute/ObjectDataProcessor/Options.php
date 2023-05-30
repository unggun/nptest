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
namespace Aheadworks\CustomerAttributes\Model\Attribute\ObjectDataProcessor;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\ObjectData\ProcessorInterface;

/**
 * Class Options
 * @package Aheadworks\CustomerAttributes\Model\Attribute\ObjectDataProcessor
 */
class Options implements ProcessorInterface
{
    /**
     * Deleted options key
     */
    const DELETED_OPTIONS = 'deleted_options';

    /**
     * {@inheritDoc}
     * @param Attribute $object
     */
    public function afterLoad($object)
    {
        return $object;
    }

    /**
     * {@inheritDoc}
     * @param Attribute $object
     */
    public function beforeSave($object)
    {
        $options = (array)$object->getData(Attribute::OPTIONS);
        $deletedOptions = (array)$object->getData(self::DELETED_OPTIONS);
        $options = array_merge($options, $deletedOptions);
        $default = [];
        $preparedOptions = null;

        if (!empty($options)) {
            $preparedOptions = [
                'order' => [],
                'value' => [],
                'delete' => []
            ];
            foreach ($options as $key => $optionData) {
                if (is_array($optionData)) {
                    $optionId = is_numeric($optionData['option_id']) ? (int)$optionData['option_id'] : 'option_' . $key;
                    $preparedOptions['order'][$optionId] = $optionData['sort_order'];
                    $preparedOptions['value'][$optionId] = $optionData['store_labels'];
                    if (isset($optionData['delete'])) {
                        $preparedOptions['delete'][$optionId] = true;
                    }
                    if (!empty($optionData['is_default'])) {
                        $default[] = $optionId;
                    }
                }
            }
        }
        $object
            ->setData('option', $preparedOptions)
            ->setData('default', $default);

        return $object;
    }
}
