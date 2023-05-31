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
namespace Aheadworks\CustomerAttributes\Block\Checkout;

use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\UsedInForms;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Eav\Model\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form\AttributeMapper;

/**
 * Class LayoutProcessor
 * @package Aheadworks\CustomerAttributes\Block\Checkout
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var AttributeMapper
     */
    private $attributeMapper;

    /**
     * @var AttributeMerger
     */
    private $merger;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var array
     */
    private $notAllowedTypes = [
        InputType::IMAGE,
        InputType::FILE
    ];

    /**
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param AttributeMapper $attributeMapper
     * @param AttributeMerger $merger
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        AttributeMapper $attributeMapper,
        AttributeMerger $merger,
        ArrayManager $arrayManager
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->attributeMapper = $attributeMapper;
        $this->merger = $merger;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritDoc}
     * @throws LocalizedException
     */
    public function process($jsLayout)
    {
        $attributes = $this->getAddressAttributes();
        $elements = $this->convertToElements($attributes);
        $jsLayout = $this->processShippingAddress($jsLayout, $elements);
        $jsLayout = $this->processBillingAddress($jsLayout, $elements);

        return $jsLayout;
    }

    /**
     * Process shipping address fields
     *
     * @param array $jsLayout
     * @param array $elements
     * @return array
     */
    private function processShippingAddress($jsLayout, $elements)
    {
        $path = 'components/checkout/children/steps/children/shipping-step/children/shippingAddress/children/'
            . 'shipping-address-fieldset/children';

        if ($data = $this->arrayManager->get($path, $jsLayout)) {
            $jsLayout = $this->arrayManager->set(
                $path,
                $jsLayout,
                $this->merger->merge(
                    $elements,
                    'checkoutProvider',
                    'shippingAddress.custom_attributes',
                    $data
                )
            );
        }

        return $jsLayout;
    }

    /**
     * Process billing address fields
     *
     * @param array $jsLayout
     * @param array $elements
     * @return array
     */
    private function processBillingAddress($jsLayout, $elements)
    {
        $paymentMethodsPath = 'components/checkout/children/steps/children/billing-step/'
            . 'children/payment/children/payments-list/children';

        foreach ((array)$this->arrayManager->get($paymentMethodsPath, $jsLayout) as $paymentCode => $paymentConfig) {
            $path = 'children/form-fields/children';
            if ($fields = $this->arrayManager->get($path, $paymentConfig)) {
                $jsLayout = $this->arrayManager->set(
                    $paymentMethodsPath . '/' . $paymentCode . '/' . $path,
                    $jsLayout,
                    $this->merger->merge(
                        $elements,
                        'checkoutProvider',
                        $this->arrayManager->get(
                            'dataScopePrefix',
                            $paymentConfig,
                            'billingAddress' . $paymentCode
                        ) . '.custom_attributes',
                        $fields
                    )
                );
            }
        }

        return $jsLayout;
    }

    /**
     * Retrieve address attributes
     *
     * @return array
     */
    private function getAddressAttributes()
    {
        $result = [];
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            UsedInForms::CUSTOMER_ADDRESS_REGISTER
        );

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute->getIsUserDefined()
                && !$attribute->isStatic()
                && !in_array($attribute->getFrontendInput(), $this->notAllowedTypes)
            ) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    /**
     * Convert to elements
     *
     * @param Attribute[] $attributes
     * @return array
     * @throws LocalizedException
     */
    private function convertToElements($attributes)
    {
        $elements = [];

        foreach ($attributes as $attribute) {
            $elements[$attribute->getAttributeCode()] = $this->attributeMapper->map($attribute);
        }

        return $elements;
    }
}
