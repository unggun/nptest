<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-19T18:52:19+00:00
 * File:          app/code/Xtento/OrderImport/Helper/Entity.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Helper;

use Magento\Framework\Exception\LocalizedException;
use Xtento\XtCore\Helper\Utils;

class Entity extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var bool
     */
    protected static $magentoMsiSupport = null;

    /**
     * @var \Xtento\OrderImport\Model\Import
     */
    protected $importModel;

    /**
     * @var Utils
     */
    protected $utilsHelper;

    /**
     * Entity constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Xtento\OrderImport\Model\Import $importModel
     * @param Utils $utilsHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Xtento\OrderImport\Model\Import $importModel,
        Utils $utilsHelper
    ) {
        parent::__construct($context);
        $this->importModel = $importModel;
        $this->utilsHelper = $utilsHelper;
    }

    /**
     * @param $entity
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getEntityName($entity)
    {
        $entities = $this->importModel->getEntities();
        if (isset($entities[$entity])) {
            return rtrim($entities[$entity], 's');
        } else {
            return __("Undefined Entity");
        }
    }

    /**
     * @param $entity
     *
     * @return mixed
     */
    public function getPluralEntityName($entity)
    {
        return $entity;
    }

    /**
     * @param $entity
     *
     * @return string
     * @throws LocalizedException
     */
    public function getEntity($entity)
    {
        if ($entity == 'order') {
            return '\Magento\Sales\Model\Order';
        }
        if ($entity == 'invoice') {
            return '\Magento\Sales\Model\Order\Invoice';
        }
        if ($entity == 'shipment') {
            return '\Magento\Sales\Model\Order\Shipment';
        }
        if ($entity == 'creditmemo') {
            return '\Magento\Sales\Model\Order\Creditmemo';
        }
        if ($entity == 'quote') {
            return '\Magento\Sales\Model\Quote';
        }
        if ($entity == 'customer') {
            return '\Magento\Customer\Model\Customer';
        }
        throw new LocalizedException(__('Could not find entity "%1"', $entity));
    }

    /**
     * @param $processor
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getProcessorName($processor)
    {
        $processors = $this->importModel->getProcessors();
        if (!array_key_exists($processor, $processors)) {
            throw new LocalizedException(__('Processor "%1" does not exist. Cannot load profile.', $processor));
        }
        $processorName = $processors[$processor];
        return $processorName;
    }

    /**
     * @return bool|mixed
     */
    public function getMagentoMSISupport()
    {
        if (self::$magentoMsiSupport !== null) {
            return self::$magentoMsiSupport;
        }

        if (!$this->utilsHelper->isExtensionInstalled('Magento_Inventory')) {
            // Don't use MSI if MSI isn't installed
            self::$magentoMsiSupport = false;
            return self::$magentoMsiSupport;
        }
        self::$magentoMsiSupport = version_compare($this->utilsHelper->getMagentoVersion(), '2.3', '>=');
        return self::$magentoMsiSupport;
    }
}
