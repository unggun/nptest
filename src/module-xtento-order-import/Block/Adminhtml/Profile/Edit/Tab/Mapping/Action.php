<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Mapping/Action.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab\Mapping;

class Action extends AbstractMapping
{
    public $mappingId = 'action';
    public $mappingModel = 'Xtento\OrderImport\Model\Processor\Mapping\Action';
    public $fieldLabel = 'Action';
    public $valueFieldLabel = 'Value';
    public $hasDefaultValueColumn = true;
    public $hasValueColumn = false;
    public $defaultValueFieldLabel = 'Value';
    public $addFieldLabel = 'Add new action';
    public $addAllFieldLabel = 'Add all actions';
    public $selectLabel = '--- Select action ---';
}
