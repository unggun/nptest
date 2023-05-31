<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Condition/Item.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Condition;

class Item extends \Magento\SalesRule\Model\Rule\Condition\Address
{
    /**
     * @var CustomFactory
     */
    protected $conditionCustomFactory;

    /**
     * Item constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Directory\Model\Config\Source\Country $directoryCountry
     * @param \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion
     * @param \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods
     * @param \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods
     * @param CustomFactory $conditionCustomFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Directory\Model\Config\Source\Country $directoryCountry,
        \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods,
        \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods,
        \Xtento\OrderImport\Model\Import\Condition\CustomFactory $conditionCustomFactory,
        array $data = []
    ) {
        $this->conditionCustomFactory = $conditionCustomFactory;
        parent::__construct(
            $context,
            $directoryCountry,
            $directoryAllregion,
            $shippingAllmethods,
            $paymentAllmethods,
            $data
        );
    }

    public function loadAttributeOptions()
    {
        $attributes = [];
        $attributes = array_merge(
            $attributes,
            $this->conditionCustomFactory->create()->getCustomNotMappedAttributes('_item')
        );
        $this->setAttributeOption($attributes);
        return $this;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'stock_id':
                return 'numeric';
        }
        // Get type for custom
        return 'string';
    }

    public function getValueElementType()
    {
        /*switch ($this->getAttribute()) {
            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }*/
        return 'text';
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            $this->setData('value_select_options', []);
        }
        return $this->getData('value_select_options');
    }

    /**
     * Validate Address Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $object)
    {
        #var_dump($this->validateAttribute($object->getData($this->getAttribute())), $object->getData($this->getAttribute()), $this->getAttribute(), $this->getValueParsed()); die();
        return $this->validateAttribute($object->getData($this->getAttribute()));
    }
}
