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
namespace Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\PostData\ProcessorInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\Formatter\Date as DateFormatter;

/**
 * Class ValidateRules
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor
 */
class ValidateRules implements ProcessorInterface
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
     * {@inheritdoc}
     * phpcs:disable Magento2.Performance
     */
    public function process($data)
    {
        if (!empty($data[AttributeInterface::VALIDATE_RULES])) {
            $validateRules = (array)$data[AttributeInterface::VALIDATE_RULES];
            $resultRules = [];
            foreach (reset($validateRules) as $ruleKey => $ruleData) {
                if (!empty($ruleData[$ruleKey])) {
                    if (strpos($ruleKey, 'date') !== false) {
                        $ruleData[$ruleKey] = $this->dateFormatter->strToTime($ruleData[$ruleKey]);
                    }
                    $resultRules = array_merge($resultRules, $ruleData);
                }
            }
            $data[AttributeInterface::VALIDATE_RULES] = $resultRules;
        }

        return $data;
    }
}
