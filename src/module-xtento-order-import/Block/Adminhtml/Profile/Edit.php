<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:11:23+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Xtento\OrderImport\Helper\Entity
     */
    protected $entityHelper;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Xtento\OrderImport\Helper\Entity $entityHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Xtento\OrderImport\Helper\Entity $entityHelper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->entityHelper = $entityHelper;
        parent::__construct($context, $data);
    }

    /**
     * Initialize edit block
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Xtento_OrderImport';
        $this->_controller = 'adminhtml_profile';
        parent::_construct();

        if ($this->registry->registry('orderimport_profile')->getId()) {
            $this->buttonList->add(
                'duplicate_button',
                [
                    'label' => __('Duplicate Profile'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('*/*/duplicate', ['_current' => true]) . '\')',
                    'class' => 'add',
                ],
                0
            );

            $this->buttonList->update('save', 'label', __('Save Profile'));
            $this->buttonList->update('delete', 'label', __('Delete Profile'));
            $this->buttonList->remove('reset');
        } else {
            $this->buttonList->remove('delete');
            $this->buttonList->remove('save');
        }

        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save primary',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ]
        );
    }
}