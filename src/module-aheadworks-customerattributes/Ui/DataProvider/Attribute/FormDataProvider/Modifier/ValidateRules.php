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
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\Formatter\Date as DateFormatter;

/**
 * Class ValidateRules
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier
 */
class ValidateRules implements ModifierInterface
{
    /**
     * @var DateFormatter
     */
    private $dateFormatter;

    /**
     * @param DateFormatter $dateFormatter
     */
    public function __construct(
        DateFormatter $dateFormatter
    ) {
        $this->dateFormatter = $dateFormatter;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        if (isset($data[AttributeInterface::VALIDATE_RULES])) {
            $resultRules = [];
            foreach ((array)$data[AttributeInterface::VALIDATE_RULES] as $ruleKey => $ruleValue) {
                if (strpos($ruleKey, 'date') !== false) {
                    $ruleValue = $this->dateFormatter->format($this->dateFormatter->timeToDate($ruleValue));
                }
                $resultRules[$ruleKey] = [$ruleKey => $ruleValue];
            }
            $data[AttributeInterface::VALIDATE_RULES] = empty($resultRules) ? [] : [$resultRules];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
