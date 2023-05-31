<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-11-13T14:30:00+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Condition/Custom.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Condition;

use Xtento\OrderImport\Model\Import;

class Custom extends \Magento\Framework\Model\AbstractModel
{
    protected $customAttributes = [];
    protected $customNotMappedAttributes = [];
    protected $omitAttributes = [];

    public function getCustomAttributes()
    {
        // Omitted attributes
        $this->omitAttributes = [
            #'status' => 'Status',
            #'state' => 'State',
        ];
        // Custom ready to use attributes
        $attributes = [
            'payment_method' => __('Payment Method'),
            'shipping_method' => __('Shipping Method'),
            'xt_billing_postcode' => __('Billing Postcode'),
            'xt_billing_region' => __('Billing Region'),
            'xt_billing_region_id' => __('Billing State/Province'),
            'xt_billing_country_id' => __('Billing Country'),
            'xt_shipping_postcode' => __('Shipping Postcode'),
            'xt_shipping_region' => __('Shipping Region'),
            'xt_shipping_region_id' => __('Shipping State/Province'),
            'xt_shipping_country_id' => __('Shipping Country'),
        ];
        $this->customAttributes = $attributes;
        return $attributes;
    }

    /*
     * Further attributes from this entity
     */
    public function getCustomNotMappedAttributes($type = '')
    {
        if (empty($this->customAttributes)) {
            $this->customAttributes = $this->getCustomAttributes();
        }
        if (!empty($this->customNotMappedAttributes)) {
            return $this->customNotMappedAttributes;
        }
        $entity = $this->_registry->registry('orderimport_profile')->getEntity();
        if ($entity != Import::ENTITY_ORDER) {
            return $this->customNotMappedAttributes;
        }
        $resource = $this->_registry->registry('orderimport_profile')->getResource();
        $columns = array_keys(
            $resource->getConnection()->describeTable($resource->getTable('sales_' . $entity . $type))
        );
        sort($columns);

        $attributes = [];
        foreach ($columns as $column) {
            if (isset($this->customAttributes[$column]) || isset($this->omitAttributes[$column])) {
                continue;
            }
            $attributes[$column] = $column;
        }
        $this->customNotMappedAttributes = $attributes;
        return $attributes;
    }
}