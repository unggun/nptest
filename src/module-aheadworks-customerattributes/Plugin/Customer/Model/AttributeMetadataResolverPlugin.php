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
namespace Aheadworks\CustomerAttributes\Plugin\Customer\Model;

use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Customer\Model\AttributeMetadataResolver;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

/**
 * Class AttributeMetadataResolverPlugin
 * @package Aheadworks\CustomerAttributes\Plugin\Customer\Model
 */
class AttributeMetadataResolverPlugin
{
    /**
     * @var array
     */
    private $triggerInputTypes = [
        InputType::FILE,
        InputType::IMAGE
    ];

    /**
     * Add dataType if needed
     *
     * @param AttributeMetadataResolver $subject
     * @param array $result
     * @param AbstractAttribute $attribute
     * @return array
     */
    public function afterGetAttributesMeta(
        AttributeMetadataResolver $subject,
        $result,
        AbstractAttribute $attribute
    ) {
        if (in_array($attribute->getFrontendInput(), $this->triggerInputTypes)
            && !isset($result['arguments']['data']['config']['dataType'])
        ) {
            $result['arguments']['data']['config']['dataType'] = 'file';
        }

        return $result;
    }
}
