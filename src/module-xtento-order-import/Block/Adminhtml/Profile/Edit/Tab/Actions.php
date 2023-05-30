<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-18T15:09:09+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Actions.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping\Action;
use Xtento\OrderImport\Block\Adminhtml\Widget\Tab;

class Actions extends Tab implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var Action
     */
    protected $mappingAction;

    /**
     * Actions constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Action $mappingAction
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Action $mappingAction,
        array $data = []
    ) {
        $this->mappingAction = $mappingAction;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function getFormMessages()
    {
        $formMessages = [];
        $formMessages[] = [
            'type' => 'notice',
            'message' => __(
                'The actions set up below will be applied to all manual and automatic imports, there is no sort order.'
            )
        ];
        return $formMessages;
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('orderimport_profile');
        if (!$model->getId()) {
            return $this;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setValues($model->getConfiguration());
        $this->setForm($form);
        $this->setTemplate('Xtento_OrderImport::profile/action.phtml');

        return parent::_prepareForm();
    }

    public function getActionHtml()
    {
        $model = $this->_coreRegistry->registry('orderimport_profile');
        $form = $this->getForm();
        $mapping = $form->addField('action', 'text', ['label' => '', 'name' => 'action']);
        $form->setValues($model->getConfiguration());
        $block = $this->mappingAction;
        // Add default action
        $profileConfiguration = $model->getConfiguration();
        $afterElementHtml = '';
        if (!isset($profileConfiguration['action']) || empty($profileConfiguration['action'])) {
            $afterElementHtml = '<script>require([\'jquery\'], function () {
setTimeout(function(){
    jQuery(\'#emptyAddBtn_action\').click(); 
    jQuery(\'#grid_action select:nth(0)\').val(\'import_' . $model->getEntity() . '\');
    setTimeout(function(){
        jQuery(\'#grid_action select:nth(1)\').val(1);
        }, 1000);
}, 1000);
});</script>';
        }
        return $block->render($mapping). $afterElementHtml;
    }

    /**
     * Prepare label for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Actions');
    }

    /**
     * Prepare title for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Actions');
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
