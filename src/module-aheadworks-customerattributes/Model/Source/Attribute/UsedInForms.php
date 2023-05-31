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
 * Class UsedInForms
 * @package Aheadworks\CustomerAttributes\Model\Source\Attribute
 */
class UsedInForms implements OptionSourceInterface
{
    /**#@+
     * Form ids values
     */
    const CUSTOMER_ACCOUNT_CREATE = 'customer_account_create';
    const CUSTOMER_ACCOUNT_EDIT = 'customer_account_edit';
    const ADMIN_CUSTOMER_ACCOUNT_EDIT = 'adminhtml_customer';
    const ADMIN_CHECKOUT = 'adminhtml_checkout';
    const ADMIN_CUSTOMER_ADDRESS = 'adminhtml_customer_address';
    const CUSTOMER_ADDRESS_EDIT = 'customer_address_edit';
    const CUSTOMER_ADDRESS_REGISTER = 'customer_register_address';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CUSTOMER_ACCOUNT_CREATE,
                'label' => __('Customer Registration')
            ],
            [
                'value' => self::CUSTOMER_ACCOUNT_EDIT,
                'label' => __('Customer Account Edit')
            ],
            [
                'value' => self::ADMIN_CHECKOUT,
                'label' => __('Admin Checkout')
            ],
            [
                'value' => self::CUSTOMER_ADDRESS_REGISTER,
                'label' => __('Customer Address Registration')
            ],
            [
                'value' => self::CUSTOMER_ADDRESS_EDIT,
                'label' => __('Customer Address Edit')
            ]
        ];
    }
}
