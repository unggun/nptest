<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Condition/Product/Found.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Condition\Product;

class Found extends \Magento\SalesRule\Model\Rule\Condition\Product\Found
{
    /**
     * @var \Xtento\OrderImport\Model\Import\Condition\Product
     */
    protected $conditionProduct;

    /**
     * @var \Xtento\OrderImport\Model\Import\Condition\Custom
     */
    protected $conditionCustom;

    /**
     * Found constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $ruleConditionProduct
     * @param \Xtento\OrderImport\Model\Import\Condition\Product $conditionProduct
     * @param \Xtento\OrderImport\Model\Import\Condition\Custom $conditionCustom
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\SalesRule\Model\Rule\Condition\Product $ruleConditionProduct,
        \Xtento\OrderImport\Model\Import\Condition\Product $conditionProduct,
        \Xtento\OrderImport\Model\Import\Condition\Custom $conditionCustom,
        array $data = []
    ) {
        $this->conditionProduct = $conditionProduct;
        $this->conditionCustom = $conditionCustom;
        parent::__construct($context, $ruleConditionProduct, $data);
        $this->setType('Xtento\OrderImport\Model\Import\Condition\Product\Found');
    }

    public function asHtml()
    {
        $html = $this->getTypeElement()->getHtml() .
            __(
                "If an item is %1 with %2 of these conditions true:",
                $this->getValueElement()->getHtml(),
                $this->getAggregatorElement()->getHtml()
            );
        if ($this->getId() != '1') {
            $html .= $this->getRemoveLinkHtml();
        }
        return $html;
    }

    public function getNewChildSelectOptions()
    {
        $productCondition = $this->conditionProduct;
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $pAttributes = [];
        foreach ($productAttributes as $code => $label) {
            $pAttributes[] = [
                'value' => 'Xtento\OrderImport\Model\Import\Condition\Product|' . $code,
                'label' => $label
            ];
        }

        $itemAttributes = [];
        $customItemAttributes = $this->conditionCustom->getCustomNotMappedAttributes('_item');
        foreach ($customItemAttributes as $code => $label) {
            $itemAttributes[] = [
                'value' => 'Xtento\OrderImport\Model\Import\Condition\Item|' . $code,
                'label' => $label
            ];
        }

        $conditions = [
            ['value' => '', 'label' => __('Please choose a condition to add.')],
            [
                'value' => 'Xtento\OrderImport\Model\Import\Condition\Combine',
                'label' => __('Conditions combination')
            ],
            ['label' => __('Product Attribute'), 'value' => $pAttributes],
            ['label' => __('Item Attribute'), 'value' => $itemAttributes],
        ];
        return $conditions;
    }
}