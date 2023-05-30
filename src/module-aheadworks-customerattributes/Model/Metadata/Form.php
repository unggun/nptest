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
namespace Aheadworks\CustomerAttributes\Model\Metadata;

use Magento\Customer\Model\Metadata\Form as CustomerForm;
use Magento\Customer\Api\Data\AttributeMetadataInterface;

/**
 * Class AdminForm
 *
 * @package Aheadworks\CustomerAttributes\Model\Metadata
 */
class Form extends CustomerForm
{
    /**
     * Retrieve user defined attributes
     *
     * @return AttributeMetadataInterface[]
     */
    public function getUserAttributes()
    {
        $result = [];
        foreach ($this->getAttributes() as $attribute) {
            if ($attribute->isUserDefined() && $attribute->isVisible()) {
                $result[$attribute->getAttributeCode()] = $attribute;
            }
        }
        return $result;
    }
}
