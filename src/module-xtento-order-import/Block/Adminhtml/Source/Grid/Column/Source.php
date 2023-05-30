<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Source/Grid/Column/Source.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Source\Grid\Column;

class Source extends \Magento\Backend\Block\Widget\Grid\Column
{
    /**
     * @var \Xtento\OrderImport\Model\ProfileFactory
     */
    protected $profileFactory;

    /**
     * Source constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Xtento\OrderImport\Model\ProfileFactory $profileFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Xtento\OrderImport\Model\ProfileFactory $profileFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->profileFactory = $profileFactory;
    }

    protected function getProfile()
    {
        return $this->profileFactory->create()->load(
            $this->getRequest()->getParam('id')
        );
    }

    public function getValues()
    {
        $array = [];
        foreach (explode("&", $this->getProfile()->getSourceIds()) as $key => $sourceId) {
            if ($sourceId === '') {
                continue;
            }
            $array[] = ['label' => $sourceId, 'value' => $sourceId];
        }
        return $array;
    }
}
