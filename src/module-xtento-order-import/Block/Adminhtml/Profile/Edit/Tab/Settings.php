<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-04T13:14:57+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Settings.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab;

use Xtento\OrderImport\Model\Import;

class Settings extends \Xtento\OrderImport\Block\Adminhtml\Widget\Tab implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesNo;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $conditions;

    /**
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Order\Identifier
     */
    protected $orderIdentifierSource;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Product\Identifier
     */
    protected $productIdentifierSource;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Import\Mode
     */
    protected $importModeSource;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Import\Customer
     */
    protected $customerModeSource;

    /**
     * Settings constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\Yesno $yesNo
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Xtento\OrderImport\Model\System\Config\Source\Order\Identifier $orderIdentifierSource
     * @param \Xtento\OrderImport\Model\System\Config\Source\Product\Identifier $productIdentifierSource
     * @param \Xtento\OrderImport\Model\System\Config\Source\Import\Mode $importModeSource
     * @param \Xtento\OrderImport\Model\System\Config\Source\Import\Customer $customerModeSource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesNo,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Xtento\OrderImport\Model\System\Config\Source\Order\Identifier $orderIdentifierSource,
        \Xtento\OrderImport\Model\System\Config\Source\Product\Identifier $productIdentifierSource,
        \Xtento\OrderImport\Model\System\Config\Source\Import\Mode $importModeSource,
        \Xtento\OrderImport\Model\System\Config\Source\Import\Customer $customerModeSource,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->conditions = $conditions;
        $this->rendererFieldset = $rendererFieldset;
        $this->orderIdentifierSource = $orderIdentifierSource;
        $this->productIdentifierSource = $productIdentifierSource;
        $this->importModeSource = $importModeSource;
        $this->customerModeSource = $customerModeSource;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function getFormMessages()
    {
        $formMessages = [];
        $formMessages[] = [
            'type' => 'notice',
            'message' => __(
                'The settings specified below will be applied to all manual and automatic imports.'
            )
        ];
        return $formMessages;
    }

    /**
     * @return $this|\Xtento\OrderImport\Block\Adminhtml\Widget\Tab
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('orderimport_profile');
        if (!$model->getId()) {
            return $this;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('settings', ['legend' => __('Import Settings'), 'class' => 'fieldset-wide',]);

        $fieldset->addField('import_mode', 'select', [
            'label' => __('Import Mode'),
            'name' => 'import_mode',
            'values' => $this->importModeSource->toOptionArray(),
            'note' => __('')
        ]
        );

        if ($model->getEntity() == Import::ENTITY_ORDER) {
            $fieldset->addField(
                'customer_mode', 'select',
                [
                    'label' => __('Customer Mode'),
                    'name' => 'customer_mode',
                    'values' => $this->customerModeSource->toOptionArray(),
                    'note' => __('')
                ]
            );

            $fieldset->addField(
                'order_identifier',
                'select',
                [
                    'label' => __('Order Identifier'),
                    'name' => 'order_identifier',
                    'values' => $this->orderIdentifierSource->toOptionArray(),
                    'note' => __(
                        'This is what is called the Order Identifier in the import settings and is what\'s used to identify the orders in the import file. Almost always you will want to use the Order Increment ID (Example: 100000001).'
                    )
                ]
            );

            $fieldset->addField(
                'product_identifier',
                'select',
                [
                    'label' => __('Product Identifier'),
                    'name' => 'product_identifier',
                    'values' => $this->productIdentifierSource->toOptionArray(),
                    'note' => __(
                        'This is what is called the Product Identifier in the import settings and is what\'s used to identify the product in the import file. Almost always you will want to use the SKU.'
                    )
                ]
            );
            $attributeCodeJs = "<script>
require([\"jquery\"], function($) {
$(document).ready(function() { 
    function checkAttributeField(field) {
        if(field.val()=='attribute') {
            \$('#product_identifier_attribute_code').parent().parent().show()
        } else {
            \$('#product_identifier_attribute_code').parent().parent().hide()
        }
    } 
    checkAttributeField(\$('#product_identifier')); 
    \$('#product_identifier').change(function(){ checkAttributeField($(this)); }); 
});
});
</script>";
            if ($model->getData('product_identifier') !== 'attribute') {
                // Not filled
                $attributeCodeJs .= "<script>
require([\"jquery\"], function($) {
\$('#product_identifier_attribute_code').parent().parent().hide()
});
</script>";
            }
            $fieldset->addField(
                'product_identifier_attribute_code',
                'text',
                [
                    'label' => __('Product Identifier: Attribute Code'),
                    'name' => 'product_identifier_attribute_code',
                    'note' => __(
                            'IMPORTANT: This is not the attribute name. It is the attribute code you assigned to the attribute.'
                        ) . $attributeCodeJs,
                ]
            );

            $fieldset->addField(
                'skip_out_of_stock_products',
                'select',
                [
                    'label' => __('Skip out of stock products'),
                    'name' => 'skip_out_of_stock_products',
                    'values' => $this->yesNo->toOptionArray(),
                    'note' => __(
                        'If enabled, out of stock products will NOT be imported in orders.'
                    )
                ]
            );
            $fieldset->addField(
                'add_comment_after_import',
                'text',
                [
                    'label' => __('Add comment to status history'),
                    'name' => 'add_comment_after_import',
                    'note' => __(
                        'Comment is added to the order status history after the order has been processed. If you don\'t want any comment to appear, enter DISABLED'
                    )
                ]
            );

            $renderer = $this->rendererFieldset
                ->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
                ->setNewChildUrl(
                    $this->getUrl(
                        'xtento_orderimport/profile/newConditionHtml/form/rule_conditions_fieldset',
                        ['profile_id' => $model->getId()]
                    )
                );

            $fieldset = $form->addFieldset(
                'rule_conditions_fieldset',
                [
                    'legend' => __(
                        'Process %1 only if... (works for existing orders only)',
                        $this->_coreRegistry->registry('orderimport_profile')->getEntity()
                    ),
                ]
            )->setRenderer($renderer);

            $fieldset->addField(
                'conditions',
                'text',
                [
                    'name' => 'conditions',
                    'label' => __('Conditions'),
                    'title' => __('Conditions'),
                ]
            )->setRule($model)->setRenderer($this->conditions);
        }

        $form->setValues($model->getConfiguration());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Settings & Filters');
    }

    /**
     * Prepare title for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Settings & Filters');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}