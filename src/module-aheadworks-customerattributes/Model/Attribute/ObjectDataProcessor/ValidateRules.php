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
 * Class ValidateRules
 * @package Aheadworks\CustomerAttributes\Model\Attribute\ObjectDataProcessor
 */
class ValidateRules implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     * @param Attribute $object
     */
    public function afterLoad($object)
    {
        $object->setData(Attribute::VALIDATE_RULES, $object->getValidateRules());
        return $object;
    }

    /**
     * {@inheritDoc}
     * @param Attribute $object
     */
    public function beforeSave($object)
    {
        return $object;
    }
}
