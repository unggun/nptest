<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-11-14T10:45:17+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Mapping/AbstractMapping.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Layout;

abstract class AbstractMapping extends AbstractFieldArray
{
    public $mappingId;
    public $mappingModel;
    public $fieldLabel;
    public $valueFieldLabel;
    public $hasDefaultValueColumn;
    public $hasValueColumn;
    public $defaultValueFieldLabel;
    public $addFieldLabel;
    public $addAllFieldLabel;
    public $selectLabel;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Layout
     */
    protected $viewLayout;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    protected $_addAllButtonLabel;

    /**
     * AbstractMapping constructor.
     *
     * @param Context $context
     * @param Registry $frameworkRegistry
     * @param Layout $viewLayout
     * @param ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $frameworkRegistry,
        Layout $viewLayout,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->registry = $frameworkRegistry;
        $this->viewLayout = $viewLayout;
        $this->objectManager = $objectManager;

        $mappingModel = $this->objectManager->create($this->mappingModel);
        $profile = $this->registry->registry('orderimport_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(
            isset($configuration[$this->mappingId]) ? $configuration[$this->mappingId] : []
        );
        try {
            $mappingFields = $mappingModel->getMappingFields();
        } catch (\Exception $e) {
            throw new LocalizedException(__('There was an exception retrieving the mapping. Exception: %s %s', $e->getMessage(), $e->getTraceAsString()));
        }

        $importFieldRenderer = $this->viewLayout->createBlock(
            'Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping\Importfields'
        );
        $importFieldRenderer->setImportFields($mappingFields);
        $importFieldRenderer->setMappingId($this->mappingId);
        $importFieldRenderer->setSelectLabel($this->selectLabel);
        $importFieldRenderer->setStyle('width: 91%');

        $this->addColumn(
            'field',
            [
                'label' => __($this->fieldLabel),
                'style' => 'width: 99.9%',
                'renderer' => $importFieldRenderer
            ]
        );

        if ($this->hasValueColumn) {
            $this->addColumn(
                'value',
                [
                    'label' => __($this->valueFieldLabel),
                    'style' => 'width:98%',
                ]
            );
        }

        if ($this->hasDefaultValueColumn) {
            $defaultValuesRenderer = $this->viewLayout->createBlock(
                'Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping\Defaultvalues'
            );
            $defaultValuesRenderer->setImportFields($mappingFields);
            $defaultValuesRenderer->setMappingModel($mappingModel);
            $defaultValuesRenderer->setMappingId($this->mappingId);
            $defaultValuesRenderer->setStyle('width: 99.9%');

            $this->addColumn(
                'default_value',
                [
                    'label' => __($this->defaultValueFieldLabel),
                    'style' => 'width: 98%',
                    'renderer' => $defaultValuesRenderer
                ]
            );
        }

        $this->_addAfter = true;
        $this->_addButtonLabel = __($this->addFieldLabel);
        $this->_addAllButtonLabel = __($this->addAllFieldLabel);
        parent::__construct($context, $data);
        $this->setTemplate('Xtento_OrderImport::widget/mapper.phtml');
    }

    public function render(AbstractElement $element)
    {
        $mappingModel = $this->objectManager->create($this->mappingModel);
        $profile = $this->registry->registry('orderimport_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(
            isset($configuration[$this->mappingId]) ? $configuration[$this->mappingId] : []
        );
        try {
            $mappingFields = $mappingModel->getMappingFields();
        } catch (\Exception $e) {
            throw new LocalizedException(__('There was an exception retrieving the mapping. Exception: %s %s', $e->getMessage(), $e->getTraceAsString()));
        }

        // Add the actual mapped fields
        $html = '<script>
require(["jquery", "prototype"], function(jQuery) {
' . "\n";
        $html .= 'window.' . $this->mappingId . '_mapping_values = new Hash();' . "\n";
        #var_dump($mappingModel->getMapping()); die();
        foreach ($mappingModel->getMapping() as $fieldId => $fieldData) {
            $html .= 'window.' . $this->mappingId . '_mapping_values[\'' . $fieldData['id'] . '\'] = \'' . $this->escapeStringJs(
                    $fieldData['field']
                ) . '\';' . "\n";
        }
        if ($this->hasDefaultValueColumn) {
            // Add the default values
            $html .= 'window.' . $this->mappingId . '_default_values = new Hash();' . "\n";
            foreach ($mappingModel->getMapping() as $fieldId => $fieldData) {
                $html .= 'window.' . $this->mappingId . '_default_values[\'' . $fieldData['id'] . '\'] = \'' . $this->escapeStringJs(
                        $fieldData['default_value']
                    ) . '\';' . "\n";
            }
            // Add default value for default values
            $html .= 'window.' . $this->mappingId . '_default_value = new Hash();' . "\n";
            foreach ($mappingFields as $field => $fieldData) {
                if (isset($fieldData['default_value'])) {
                    $html .= 'window.' . $this->mappingId . '_default_value[\'' . $field . '\'] = \'' . $this->escapeStringJs(
                            $fieldData['default_value']
                        ) . '\';' . "\n";
                }
            }
            // Add the possible default values
            $html .= 'window.' . $this->mappingId . '_possible_default_values = $H({' . "\n";
            $loopLength = 0;
            foreach ($mappingFields as $field => $fieldData) {
                if (isset($fieldData['default_values']) && is_array($fieldData['default_values'])) {
                    $loopLength++;
                }
            }
            $loopCounter = 0;
            foreach ($mappingFields as $field => $fieldData) {
                if (isset($fieldData['default_values']) && is_array($fieldData['default_values'])) {
                    $loopCounter++;
                    $loopLength2 = count($fieldData['default_values']);
                    $loopCounter2 = 0;
                    $html .= '\'' . $this->escapeStringJs($field) . '\': {' . "\n";
                    foreach ($fieldData['default_values'] as $code => $label) {
                        $loopCounter2++;
                        $html .= '\'' . $this->escapeStringJs($code) . '\': \'' . $this->escapeStringJs(
                                $label
                            ) . '\'';
                        if ($loopCounter2 !== $loopLength2) {
                            $html .= ',';
                        }
                        $html .= "\n";
                    }
                    $html .= '}';
                    if ($loopCounter !== $loopLength) {
                        $html .= ',';
                    }
                }
            }
            $html .= '});';
        } else {
            $html .= 'window.' . $this->mappingId . '_default_values = new Hash();' . "\n";
            $html .= 'window. ' . $this->mappingId . '_possible_default_values = $H({});' . "\n";
        }
        $html .= '
});
</script>' . "\n";

        $html .= parent::render($element);

        return $html;
    }

    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new LocalizedException(__('Wrong column name specified.'));
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }

        return '<input type="text" id="' . $inputName . '" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
        ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
        (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
        (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }

    public function escapeStringJs($string)
    {
        return str_replace(["'", "\n", "\r"], ["\\'", " ", " "], str_replace("\\", "\\\\", $string));
    }

    public function getMappingFields()
    {
        $mappingModel = $this->objectManager->create($this->mappingModel);
        $profile = $this->registry->registry('orderimport_profile');
        $configuration = $profile->getConfiguration();
        $mappingModel->setMappingData(
            isset($configuration[$this->mappingId]) ? $configuration[$this->mappingId] : []
        );

        try {
            $mappingFields = $mappingModel->getMappingFields();
        } catch (\Exception $e) {
            throw new LocalizedException(__('There was an exception retrieving the mapping. Exception: %s %s', $e->getMessage(), $e->getTraceAsString()));
        }
        return $mappingFields;
    }

    public function getAddAllButtonLabel()
    {
        return $this->_addAllButtonLabel;
    }

    public function getAddButtonLabel()
    {
        return $this->_addButtonLabel;
    }

    public function getJs($filename)
    {
        $url = $this->_assetRepo->createAsset(
            'Xtento_OrderImport::js/' . $filename,
            ['_secure' => $this->getRequest()->isSecure()]
        )->getUrl();
        return $url;
    }
}
