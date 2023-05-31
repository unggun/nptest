<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-09T14:41:01+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Entity/AbstractEntity.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Entity;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;

abstract class AbstractEntity extends DataObject
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Resource models, read/write adapater
     */
    /** @var $readAdapter \Magento\Framework\Db\Adapter\Pdo\Mysql */
    protected $readAdapter;

    /** @var $writeAdapter \Magento\Framework\Db\Adapter\Pdo\Mysql */
    protected $writeAdapter;

    /**
     * Database table name cache
     */
    protected $tableNames = [];

    /**
     * AbstractEntity constructor.
     *
     * @param array $data
     * @param ResourceConnection $resourceConnection
     * @param Registry $frameworkRegistry
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Registry $frameworkRegistry,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->registry = $frameworkRegistry;

        $this->readAdapter = $this->resourceConnection->getConnection('core_read');
        $this->writeAdapter = $this->resourceConnection->getConnection('core_write');

        parent::__construct($data);
    }

    /**
     * Get database table name for entity
     *
     * @param $entity
     *
     * @return bool
     */
    protected function getTableName($entity)
    {
        if (!isset($this->tableNames[$entity])) {
            try {
                $this->tableNames[$entity] = $this->resourceConnection->getTableName($entity);
            } catch (\Exception $e) {
                return false;
            }
        }
        return $this->tableNames[$entity];
    }

    /**
     * Return configuration value
     *
     * @param $key
     *
     * @return bool
     */
    public function getConfig($key)
    {
        $configuration = $this->getProfile()->getConfiguration();
        if (isset($configuration[$key])) {
            return $configuration[$key];
        } else {
            return false;
        }
    }

    public function getConfigFlag($key)
    {
        return (bool)$this->getConfig($key);
    }

    public function getLogEntry()
    {
        return $this->registry->registry('orderimport_log');
    }

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
                    /*if ($fieldToRetrieve == 'default_value') {
                        $manipulatedFieldValue = $this->actionConfiguration->setValueBasedOnFieldData(
                            $this->registry->registry('xtento_orderimport_updatedata'),
                            $actionData['config']
                        );
                        if ($manipulatedFieldValue !== -99) {
                            $actionData['default_value'] = $manipulatedFieldValue;
                        }
                    }*/
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
}