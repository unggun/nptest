<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-05T13:48:25+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Action/AbstractAction.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Action;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Xtento\OrderImport\Model\Processor\Mapping\Action\Configuration;

abstract class AbstractAction extends AbstractModel
{
    /**
     * @var Configuration
     */
    protected $actionConfiguration;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * AbstractAction constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Configuration $actionConfiguration
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Configuration $actionConfiguration,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->actionConfiguration = $actionConfiguration;
        $this->registry = $registry;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected $debugMessages = [];
    protected $hasUpdatedObject = false;

    public function getActionSettingByField($fieldName, $fieldToRetrieve)
    {
        if ($fieldToRetrieve == 'enabled' || $fieldToRetrieve == 'value') {
            $fieldToRetrieve = 'default_value'; // "Enabled" and "value" are synonyms and are both stored in the default_value field
        }
        $actions = $this->getActions();
        foreach ($actions as $actionId => $actionData) {
            if ($actionData['field'] == $fieldName) {
                if (isset($actionData[$fieldToRetrieve])) {
                    #var_dump($actionData[$fieldToRetrieve]); die();
                    if ($fieldToRetrieve == 'default_value') {
                        $manipulatedFieldValue = $this->actionConfiguration->setValueBasedOnFieldData(
                            $this->registry->registry('xtento_orderimport_updatedata'),
                            $actionData['config']
                        );
                        if ($manipulatedFieldValue !== -99) {
                            $actionData['default_value'] = $manipulatedFieldValue;
                        }
                    }
                    return $actionData[$fieldToRetrieve];
                } else {
                    return "";
                }
            }
        }
        return false;
    }

    public function getActionSettingByFieldBoolean($fieldName, $fieldToRetrieve)
    {
        return (bool)$this->getActionSettingByField($fieldName, $fieldToRetrieve);
    }

    protected function addDebugMessage($message)
    {
        array_push($this->debugMessages, $message);
        return $this;
    }

    public function getDebugMessages()
    {
        return (array)$this->debugMessages;
    }

    protected function setHasUpdatedObject($bool)
    {
        $this->hasUpdatedObject = $bool;
        return $this;
    }

    public function getHasUpdatedObject()
    {
        return (bool)$this->hasUpdatedObject;
    }

    protected function getProfile()
    {
        return $this->registry->registry('orderimport_profile');
    }

    protected function getProfileConfiguration()
    {
        return new DataObject($this->getProfile()->getConfiguration());
    }
}