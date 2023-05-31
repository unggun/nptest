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
namespace Aheadworks\CustomerAttributes\ViewModel\Customer\Form;

use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Form as FormAttributes;
use Magento\Store\Model\StoreResolver;

/**
 * Class Edit
 * @package Aheadworks\CustomerAttributes\ViewModel\Customer\Form
 */
class Edit extends AbstractView implements ArgumentInterface
{
    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var array
     */
    private $customerData = [];

    /**
     * @param ElementFactory $elementFactory
     * @param FormAttributes $formAttributes
     * @param StoreResolver $storeResolver
     * @param FormFactory $formFactory
     * @param CustomerRegistry $customerRegistry
     * @param Session $session
     * @param array $skipAttributes
     * @param array $renderers
     */
    public function __construct(
        ElementFactory $elementFactory,
        FormAttributes $formAttributes,
        StoreResolver $storeResolver,
        FormFactory $formFactory,
        CustomerRegistry $customerRegistry,
        Session $session,
        array $skipAttributes = [],
        array $renderers = []
    ) {
        parent::__construct(
            $elementFactory,
            $formAttributes,
            $storeResolver,
            $formFactory,
            $skipAttributes,
            $renderers
        );
        $this->customerRegistry = $customerRegistry;
        $this->session = $session;
        $this->formCode = UsedInForms::CUSTOMER_ACCOUNT_EDIT;
    }

    /**
     * Retrieve customer
     *
     * @return Customer|null
     */
    private function getCustomer()
    {
        if (!$this->customer) {
            $customerId = $this->session->getCustomerId();
            try {
                $this->customer = $this->customerRegistry->retrieve($customerId);
            } catch (LocalizedException $e) {
                $this->customer = null;
            }
        }

        return $this->customer;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    private function getCustomerData()
    {
        if (empty($this->customerData) && $customer = $this->getCustomer()) {
            $this->customerData = $customer->getData();
        }

        return $this->customerData;
    }

    /**
     * {@inheritDoc}
     */
    protected function getValue($attribute)
    {
        $defaultValue = $attribute->getDefaultValue();
        $customerData = $this->getCustomerData();
        $attributeCode = $attribute->getAttributeCode();

        return isset($customerData[$attributeCode]) && $customerData[$attributeCode] != ''
            ? $customerData[$attributeCode]
            : $defaultValue;
    }
}
