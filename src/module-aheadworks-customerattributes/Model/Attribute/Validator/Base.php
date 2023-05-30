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
namespace Aheadworks\CustomerAttributes\Model\Attribute\Validator;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Validator\AbstractValidator;
use Magento\Eav\Model\AttributeDataFactory;
use Aheadworks\CustomerAttributes\Model\Attribute\Formatter\Date as DateFormatter;

/**
 * Class Base
 * @package Aheadworks\CustomerAttributes\Model\Attribute\Validator
 */
class Base extends AbstractValidator
{
    /**
     * @var AttributeDataFactory
     */
    private $attrDataFactory;

    /**
     * @var DateFormatter
     */
    private $dateFormatter;

    /**
     * @param AttributeDataFactory $attrDataFactory
     * @param DateFormatter $dateFormatter
     */
    public function __construct(
        AttributeDataFactory $attrDataFactory,
        DateFormatter $dateFormatter
    ) {
        $this->attrDataFactory = $attrDataFactory;
        $this->dateFormatter = $dateFormatter;
    }

    /**
     * Returns true if entity meets the validation requirements
     *
     * @param Attribute $attribute
     * @return bool
     * @throws \Exception
     */
    public function isValid($attribute)
    {
        $usedInForms = (array)$attribute->getUsedInForms();
        $addressFrontendForms = [UsedInForms::CUSTOMER_ADDRESS_REGISTER, UsedInForms::CUSTOMER_ADDRESS_EDIT];
        $customerFrontendForms = [UsedInForms::CUSTOMER_ACCOUNT_CREATE, UsedInForms::CUSTOMER_ACCOUNT_EDIT];
        $customerAdminFrontendForms = [UsedInForms::ADMIN_CHECKOUT, UsedInForms::ADMIN_CUSTOMER_ACCOUNT_EDIT];
        $errors = [];

        if ($attribute->getDefaultValue() || $attribute->getDefaultValue() === '0') {
            $defaultValue = $attribute->getDefaultValue();
            if ($attribute->getFrontendInput() == InputType::DATE) {
                $defaultValue = $this->dateFormatter->format($defaultValue);
            }
            $dataModel = $this->attrDataFactory->create($attribute, $attribute);
            $defValueErrors = $dataModel->validateValue($defaultValue);
            if ($defValueErrors !== true && count((array)$defValueErrors)) {
                $errors[] = __('Default Value validation problem.');
                $errors = array_merge($errors, $defValueErrors);
            }
        }

        $msg =  __('Please either set at least one place where to display the attribute,'
            . ' or disable showing it on store front.');
        $isCustomerIntersect = array_intersect($customerFrontendForms, $usedInForms);
        $isCustomerAdminIntersect = array_intersect($customerAdminFrontendForms, $usedInForms);
        if ($attribute->getIsRequired() && $attribute->getIsVisible()) {
            if ($attribute->getEntityTypeCode() === AddressMetadataInterface::ENTITY_TYPE_ADDRESS
                && !array_intersect($addressFrontendForms, $usedInForms)
            ) {
                $errors[] = $msg;
            }
        }
        if ($attribute->getEntityTypeCode() === CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER) {
            if (($attribute->getIsRequired() && $attribute->getIsVisible()
                    && (!$isCustomerIntersect && !$isCustomerAdminIntersect))
                || ($attribute->getIsRequired() && !$attribute->getIsVisible() && $isCustomerAdminIntersect)
            ) {
                $errors[] = $msg;
            }
        }

        $this->_addMessages($errors);

        return empty($this->getMessages());
    }
}
