<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Source/Edit/Tab/Type/Custom.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Source\Edit\Tab\Type;

class Custom extends AbstractType
{
    // Custom Type Configuration
    public function getFields(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'config_fieldset',
            [
                'legend' => __('Custom Type Configuration'),
                'class' => 'fieldset-wide'
            ]
        );

        $fieldset->addField(
            'custom_class',
            'text',
            [
                'label' => __('Custom Class Identifier'),
                'name' => 'custom_class',
                'note' => __(
                    'You can set up an own class in our (or another) module which gets called when importing. The loadFiles() function would be called in your class. If your class was called \Xtento\OrderImport\Model\Source\Myclass then the identifier to enter here would be \Xtento\OrderImport\Model\Source\Myclass<br/><br/>The loadFiles() function needs to return an array like this: array(array(\'source_id\' => $this->getSource()->getId(), \'filename\' => $filename, \'data\' => $fileContents))'
                ),
                'required' => true
            ]
        );
    }
}