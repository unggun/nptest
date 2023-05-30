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
namespace Aheadworks\CustomerAttributes\ViewModel\Customer\Address;

use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Aheadworks\CustomerAttributes\ViewModel\Customer\Form\AbstractView;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Form as FormAttributes;
use Magento\Store\Model\StoreResolver;

/**
 * Class Form
 * @package Aheadworks\CustomerAttributes\ViewModel\Customer\Address
 */
class Form extends AbstractView implements ArgumentInterface
{
    /**
     * @var AddressRegistry
     */
    private $addressRegistry;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $addressData = [];

    /**
     * @param ElementFactory $elementFactory
     * @param FormAttributes $formAttributes
     * @param StoreResolver $storeResolver
     * @param FormFactory $formFactory
     * @param AddressRegistry $addressRegistry
     * @param Session $session
     * @param RequestInterface $request
     * @param array $skipAttributes
     * @param array $renderers
     */
    public function __construct(
        ElementFactory $elementFactory,
        FormAttributes $formAttributes,
        StoreResolver $storeResolver,
        FormFactory $formFactory,
        AddressRegistry $addressRegistry,
        Session $session,
        RequestInterface $request,
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
        $this->addressRegistry = $addressRegistry;
        $this->session = $session;
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {
        $this->formCode = UsedInForms::CUSTOMER_ADDRESS_EDIT;

        return parent::getAttributes();
    }

    /**
     * Retrieve address
     *
     * @return Address|null
     */
    private function getAddress()
    {
        if (!$this->address) {
            $customerId = $this->session->getCustomerId();
            try {
                $address = $this->addressRegistry->retrieve($this->request->getParam('id'));
                if ($address->getCustomerId() == $customerId) {
                    $this->address = $address;
                }
            } catch (LocalizedException $e) {
                $this->address = null;
            }
        }

        return $this->address;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    private function getAddressData()
    {
        if (empty($this->addressData) && $address = $this->getAddress()) {
            $this->addressData = $address->getData();
        }

        return $this->addressData;
    }

    /**
     * {@inheritDoc}
     */
    protected function getValue($attribute)
    {
        $defaultValue = $attribute->getDefaultValue();
        $addressData = $this->getAddressData();
        $attributeCode = $attribute->getAttributeCode();

        return isset($addressData[$attributeCode]) && $addressData[$attributeCode] != ''
            ? $addressData[$attributeCode]
            : $defaultValue;
    }
}
