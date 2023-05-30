<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-25T12:02:05+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Source/Edit/Tab/Configuration.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Source\Edit\Tab;

use Xtento\OrderImport\Model\Source;

class Configuration extends \Xtento\OrderImport\Block\Adminhtml\Widget\Tab implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesNo;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Import\Entity
     */
    protected $importEntity;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Source\Type
     */
    protected $sourceType;

    /**
     * Configuration constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\Yesno $yesNo
     * @param \Xtento\OrderImport\Model\System\Config\Source\Import\Entity $importEntity
     * @param \Xtento\OrderImport\Model\System\Config\Source\Source\Type $sourceType
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesNo,
        \Xtento\OrderImport\Model\System\Config\Source\Import\Entity $importEntity,
        \Xtento\OrderImport\Model\System\Config\Source\Source\Type $sourceType,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->importEntity = $importEntity;
        $this->sourceType = $sourceType;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('orderimport_source');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Source Settings'),
            ]
        );

        if ($model->getId()) {
            $fieldset->addField(
                'source_id',
                'hidden',
                [
                    'name' => 'source_id',
                ]
            );
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'label' => __('Name'),
                'name' => 'name',
                'required' => true,
                'note' => __('Assign a name to identify this source in logs/profiles.')
            ]
        );

        if ($model->getId()) {
            $typeNote = 'Changing the source type will reload the page.';
        } else {
            $typeNote = '';
        }

        $fieldset->addField(
            'type',
            'select',
            [
                'label' => __('Source Type'),
                'name' => 'type',
                'options' => array_merge(['' => __('--- Please Select ---')], $this->sourceType->toOptionArray()),
                'required' => true,
                'onchange' => ($model->getId(
                )) ? 'if (this.value==\'\') { return false; } edit_form.action = edit_form.action+\'continue/edit/switch/true/back/true\'; edit_form.submit();' : '',
                'note' => __($typeNote)
            ]
        );

        if (!$model->getId()) {
            $fieldset->addField(
                'continue_button',
                'note',
                [
                    'text' => $this->getChildHtml('continue_button'),
                ]
            );
        }

        if ($model->getId()) {
            $fieldset->addField(
                'status',
                'text',
                [
                    'label' => __('Status'),
                    'name' => 'status',
                    'disabled' => true,
                ]
            );
            $model->setStatus(__('Used in %1 profile(s)', count($model->getProfileUsage())));

            $fieldset->addField(
                'last_result_message',
                'textarea',
                [
                    'label' => __('Last Result Message'),
                    'name' => 'last_result_message_dis',
                    'disabled' => true,
                    'style' => 'height: 90px',
                ]
            );

            $this->addFieldsForType($form, $model->getType());

            if ($model->getType() == Source::TYPE_FTP) {
                // Define field dependencies
                $this->setChild(
                    'form_after',
                    $this->getLayout()->createBlock(
                        \Magento\Backend\Block\Widget\Form\Element\Dependence::class
                    )->addFieldMap('ftp_ignorepasvaddress', 'ftp_ignorepasvaddress')
                        ->addFieldMap('ftp_pasv', 'ftp_pasv')
                        ->addFieldDependence(
                            'ftp_ignorepasvaddress',
                            'ftp_pasv',
                            '1'
                        )
                );
            }
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function addFieldsForType($form, $type)
    {
        return $this->getLayout()->getBlockSingleton(
            '\Xtento\OrderImport\Block\Adminhtml\Source\Edit\Tab\Type\\' . ucfirst($type)
        )->getFields($form);
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'continue_button',
            $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(
                    [
                        'label' => __('Continue'),
                        'data_attribute' => [
                            'mage-init' => [
                                'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                            ],
                        ],
                        'class' => 'save'
                    ]
                )
        );
        return parent::_prepareLayout();
    }

    /**
     * Prepare label for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Configuration');
    }

    /**
     * Prepare title for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Configuration');
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