<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-19T14:49:55+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Mapping.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab;

use Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping\Mapper;

class Mapping extends \Xtento\OrderImport\Block\Adminhtml\Widget\Tab implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesNo;

    /**
     * @var \Xtento\OrderImport\Helper\Entity
     */
    protected $entityHelper;

    /**
     * @var Mapper
     */
    protected $mappingMapper;

    /**
     * @var \Xtento\OrderImport\Model\Processor\Mapping\FieldsFactory
     */
    protected $mappingFieldsFactory;

    /**
     * Mapping constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Config\Model\Config\Source\Yesno $yesNo
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Xtento\OrderImport\Helper\Entity $entityHelper
     * @param \Xtento\OrderImport\Model\Processor\Mapping\FieldsFactory $mappingFieldsFactory
     * @param Mapper $mappingMapper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Config\Model\Config\Source\Yesno $yesNo,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Xtento\OrderImport\Helper\Entity $entityHelper,
        \Xtento\OrderImport\Model\Processor\Mapping\FieldsFactory $mappingFieldsFactory,
        Mapper $mappingMapper,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->entityHelper = $entityHelper;
        $this->mappingMapper = $mappingMapper;
        $this->mappingFieldsFactory = $mappingFieldsFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function getFormMessages()
    {
        $formMessages = [];
        $profile = $this->_coreRegistry->registry('orderimport_profile');
        $formMessages[] = [
            'type' => 'notice',
            'message' => __(
                'This is the import processor for imported %1 files. Your import format needs to be mapped to Magento fields here.',
                $this->entityHelper->getProcessorName($profile->getProcessor())
            )
        ];
        return $formMessages;
    }

    /**
     * Prepare form
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $profile = $this->_coreRegistry->registry('orderimport_profile');
        if (!$profile->getId()) {
            return $this;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'manual_fieldset',
            [
                'legend' => __('File Settings'),
                'class' => 'fieldset-wide',
            ]
        );

        $fieldset->addField(
            'mapping_note',
            'note',
            [
                'text' => __(
                    '<strong>Notice</strong>: Please make sure to visit our <a href="https://support.xtento.com/wiki/Magento_2_Extensions:Order_Import_Module" target="_blank">support wiki</a> for an explanation on how to set up this processor.'
                )
            ]
        );

        if ($profile->getProcessor() == \Xtento\OrderImport\Model\Import::PROCESSOR_CSV || $profile->getProcessor() == \Xtento\OrderImport\Model\Import::PROCESSOR_SPREADSHEET) {
            $fieldset->addField(
                'skip_header',
                'select',
                [
                    'label' => __('Skip header line'),
                    'name' => 'skip_header',
                    'values' => $this->yesNo->toOptionArray(),
                    'note' => __(
                        'IMPORTANT: Set this to "Yes" if you want to skip the first line of each imported file as it\'s the header line containing the column names.'
                    )
                ]
            );
        }

        if ($profile->getProcessor() == \Xtento\OrderImport\Model\Import::PROCESSOR_CSV) {
            $fieldset->addField(
                'delimiter',
                'text',
                [
                    'label' => __('Field Delimiter'),
                    'name' => 'delimiter',
                    'note' => __(
                        'REQUIRED: Set the field delimiter (one character only). Example field delimiter: ;<br/>Hint: If you want to use a tab delimited file enter: \t'
                    ),
                    'required' => true
                ]
            );

            $fieldset->addField(
                'enclosure',
                'text',
                [
                    'label' => __('Field Enclosure Character'),
                    'name' => 'enclosure',
                    'maxlength' => 1,
                    'note' => __('Set the field enclosure character (<b>one</b> character only, if fields are wrapped in quotes for example). Example: "')
                ]
            );
        }

        if ($profile->getProcessor() == \Xtento\OrderImport\Model\Import::PROCESSOR_XML) {
            $fieldset->addField(
                'xpath_data',
                'text',
                [
                    'label' => __('Data XPath'),
                    'name' => 'xpath_data',
                    'note' => __(
                        'Set the XPath for the node containing the ' . $profile->getEntity() . ' updates.<br/><br/>Example XML file:<br/>&lt;' . $profile->getEntity() . 's&gt;<br/>&lt;' . $profile->getEntity() . '&gt;<br/>...<br/>&lt;/' . $profile->getEntity() . '&gt;<br/>&lt;' . $profile->getEntity() . '&gt;<br/>...<br/>&lt;/' . $profile->getEntity() . '&gt;<br/>&lt;/' . $profile->getEntity() . 's&gt;<br/><br/>The ' . $profile->getEntity() . ' updates would be located in each "item" node, which are located in the "' . $profile->getEntity() . '" node, so the XPath would be: //' . $profile->getEntity() . 's/' . $profile->getEntity() . '<br/><br/>Every "' . $profile->getEntity() . '" node located under the "' . $profile->getEntity() . 's" node would be processed then.'
                    ),
                    'required' => true
                ]
            );
        }

        $profile = $this->_coreRegistry->registry('orderimport_profile');
        $form->setValues($profile->getConfiguration());
        $this->setForm($form);
        $this->setTemplate('Xtento_OrderImport::profile/mapping.phtml');
        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    public function getMappingHtml()
    {
        $model = $this->_coreRegistry->registry('orderimport_profile');
        $form = $this->getForm();
        $mapping = $form->addField('mapping', 'text', ['label' => '', 'name' => 'mapping']);
        $form->setValues($model->getConfiguration());
        return $this->mappingMapper->render($mapping);
    }

    /**
     * @return mixed
     */
    public function getMappingFields()
    {
        $mappingModel = $this->mappingFieldsFactory->create();
        $profile = $this->_coreRegistry->registry('orderimport_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(isset($configuration['mapping']) ? $configuration['mapping'] : []);
        return $mappingModel->getMappingFields();
    }

    /**
     * Prepare label for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('File Mapping');
    }

    /**
     * Prepare title for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('File Mapping');
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