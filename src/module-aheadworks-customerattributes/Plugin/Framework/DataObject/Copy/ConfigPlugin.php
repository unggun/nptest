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
namespace Aheadworks\CustomerAttributes\Plugin\Framework\DataObject\Copy;

use Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor\Composite as FieldsetProcessor;
use Magento\Framework\DataObject\Copy\Config;

/**
 * Class ConfigPlugin
 * @package Aheadworks\CustomerAttributes\Plugin\Framework\DataObject\Copy
 */
class ConfigPlugin
{
    /**
     * @var FieldsetProcessor
     */
    private $fieldsetProcessor;

    /**
     * @param FieldsetProcessor $fieldsetProcessor
     */
    public function __construct(
        FieldsetProcessor $fieldsetProcessor
    ) {
        $this->fieldsetProcessor = $fieldsetProcessor;
    }

    /**
     * Add attribute codes to fieldset
     *
     * @param Config $subject
     * @param array $result
     * @param string $name
     * @return array
     */
    public function afterGetFieldset(Config $subject, $result, $name)
    {
        return $this->fieldsetProcessor->process($result, $name);
    }
}
