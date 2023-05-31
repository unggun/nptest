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

/**
 * Class InputFilter
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class InputFilter implements OptionSourceInterface
{
    /**#@+
     * Input filter values
     */
    const STRIP_TAGS = 'striptags';
    const ESCAPE_HTML = 'escapehtml';
    const DATE = 'date';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::STRIP_TAGS,
                'label' => __('Strip HTML Tags')
            ],
            [
                'value' => self::ESCAPE_HTML,
                'label' => __('Escape HTML Entities')
            ],
            [
                'value' => self::DATE,
                'label' => __('Normalize Date')
            ]
        ];
    }
}
