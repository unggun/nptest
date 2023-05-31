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
 * Class InputValidation
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class InputValidation implements OptionSourceInterface
{
    /**#@+
     * Input validation values
     */
    const ALPHANUMERIC = 'alphanumeric';
    const ALPHANUMERIC_WITH_SPACES = 'alphanum-with-spaces';
    const NUMERIC = 'numeric';
    const ALPHA = 'alpha';
    const URL = 'url';
    const EMAIL = 'email';
    const DATE = 'date';
    const LENGTH = 'length';
    const NONE = '';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ALPHANUMERIC,
                'label' => __('Alphanumeric')
            ],
            [
                'value' => self::ALPHANUMERIC_WITH_SPACES,
                'label' => __('Alphanumeric With Spaces')
            ],
            [
                'value' => self::NUMERIC,
                'label' => __('Numeric Only')
            ],
            [
                'value' => self::ALPHA,
                'label' => __('Alpha Only')
            ],
            [
                'value' => self::URL,
                'label' => __('URL')
            ],
            [
                'value' => self::EMAIL,
                'label' => __('Email')
            ],
            [
                'value' => self::DATE,
                'label' => __('Date')
            ],
            [
                'value' => self::LENGTH,
                'label' => __('Length Only')
            ]
        ];
    }
}
