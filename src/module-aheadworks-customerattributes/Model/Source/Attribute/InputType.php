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

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype as EavInputtype;

/**
 * Class InputType
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class InputType extends EavInputtype implements OptionSourceInterface
{
    /**#@+
     * Input type values
     */
    const TEXT = 'text';
    const TEXTAREA = 'textarea';
    const MULTILINE = 'multiline';
    const DATE = 'date';
    const BOOL = 'boolean';
    const MULTISELECT = 'multiselect';
    const DROPDOWN = 'select';
    const FILE = 'file';
    const IMAGE = 'image';
    /**#@-*/

    /**
     * {@inheritdoc}
     * Multiline type is omitted due to MAGETWO-44182
     * see \Magento\Eav\Model\Entity\AbstractEntity::_collectSaveData()
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TEXT,
                'label' => __('Text Field')
            ],
            [
                'value' => self::TEXTAREA,
                'label' => __('Text Area')
            ],
//            [
//                'value' => self::MULTILINE,
//                'label' => __('Multiple Line')
//            ],
            [
                'value' => self::DATE,
                'label' => __('Date')
            ],
            [
                'value' => self::BOOL,
                'label' => __('Yes/No')
            ],
            [
                'value' => self::MULTISELECT,
                'label' => __('Multiple Select')
            ],
            [
                'value' => self::DROPDOWN,
                'label' => __('Dropdown')
            ],
            [
                'value' => self::FILE,
                'label' => __('File (attachment)')
            ],
            [
                'value' => self::IMAGE,
                'label' => __('Image File')
            ]
        ];
    }
}
