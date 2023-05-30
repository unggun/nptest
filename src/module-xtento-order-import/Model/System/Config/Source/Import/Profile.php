<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Model/System/Config/Source/Import/Profile.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\System\Config\Source\Import;

class Profile
{
    /**
     * @var \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory
     */
    protected $profileCollectionFactory;

    /**
     * @param \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory
     */
    public function __construct(
        \Xtento\OrderImport\Model\ResourceModel\Profile\CollectionFactory $profileCollectionFactory
    ) {
        $this->profileCollectionFactory = $profileCollectionFactory;
    }

    public function toOptionArray($all = false, $entity = false)
    {
        $profileCollection = $this->profileCollectionFactory->create();
        if (!$all) {
            $profileCollection->addFieldToFilter('enabled', 1);
        }
        if ($entity) {
            $profileCollection->addFieldToFilter('entity', $entity);
        }
        $profileCollection->getSelect()->order('entity ASC');
        $returnArray = [];
        foreach ($profileCollection as $profile) {
            $returnArray[] = [
                'profile' => $profile,
                'value' => $profile->getId(),
                'label' => $profile->getName(),
                'entity' => $profile->getEntity(),
            ];
        }
        if (empty($returnArray)) {
            $returnArray[] = [
                'profile' => new \Magento\Framework\DataObject(),
                'value' => '',
                'label' => __(
                    'No profiles available. Add and enable import profiles for the %1 entity first.',
                    $entity
                ),
                'entity' => '',
            ];
        }
        return $returnArray;
    }
}
