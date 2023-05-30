<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/ResourceModel/Profile.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\ResourceModel;

class Profile extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_serializableFields = [
        'configuration' => [null, []]
    ];

    protected function _construct()
    {
        $this->_init('xtento_orderimport_profile', 'profile_id');
    }
}
