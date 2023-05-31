<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Source/Edit/Tab/Type/Webservice.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Source\Edit\Tab\Type;

class Webservice extends AbstractType
{
    // Webservice Configuration
    public function getFields(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'config_fieldset',
            [
                'legend' => __('Webservice Configuration'),
                'class' => 'fieldset-wide'
            ]
        );

        $fieldset->addField(
            'webservice_note',
            'note',
            [
                'text' => __(
                    '<b>Instructions</b>: To import data from a webservice, please follow the following steps:<br>1) Go into the <i>app/code/Xtento/OrderImport/Model/Source/</i> directory and rename the file "Webservice.php.sample" to "Webservice.php"<br>2) Enter the function name you want to call in the Webservice.php class in the field below.<br>3) Open the Webservice.php file and add a function that matches the function name you entered. This function will be called by this source upon importing then.<br><br><b>Example:</b> If you enter server1 in the function name field below, a method called server1() must exist in the Webservice.php file. This way multiple webservices can be added to the Webservice class, and can be called from different import source, separated by the function name that is called. The function you add then gets called whenever this source is executed by an import profile.<br/><br/><b>Important:</b> The custom function needs to return an array like this: array(array(\'source_id\' => $this->getSource()->getId(), \'filename\' => $filename, \'data\' => $fileContents))'
                )
            ]
        );

        $fieldset->addField(
            'custom_function',
            'text',
            [
                'label' => __('Custom Function'),
                'name' => 'custom_function',
                'note' => __(
                    'Please make sure the function you enter exists like this in the app/code/OrderImport/Model/Source/Webservice.php file:<br>public function <i>yourFunctionName</i>() { ... }'
                ),
                'required' => true
            ]
        );
    }
}