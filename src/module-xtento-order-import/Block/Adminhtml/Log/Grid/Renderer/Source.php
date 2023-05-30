<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Log/Grid/Renderer/Source.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Log\Grid\Renderer;

class Source extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public static $sources = [];

    /**
     * @var \Xtento\OrderImport\Model\SourceFactory
     */
    protected $sourceFactory;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Source\Type
     */
    protected $sourceType;

    /**
     * Source constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Xtento\OrderImport\Model\SourceFactory $sourceFactory
     * @param \Xtento\OrderImport\Model\System\Config\Source\Source\Type $sourceType
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Xtento\OrderImport\Model\SourceFactory $sourceFactory,
        \Xtento\OrderImport\Model\System\Config\Source\Source\Type $sourceType,
        array $data = []
    ) {
        $this->sourceFactory = $sourceFactory;
        $this->sourceType = $sourceType;
        parent::__construct($context, $data);
    }

    /**
     * Render log
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $sourceIds = $row->getSourceIds();
        $sourceText = "";
        if (empty($sourceIds)) {
            return __('No source selected. Enable in the "Import Sources" tab of the profile.');
        }
        foreach (explode("&", $sourceIds) as $sourceId) {
            if (!empty($sourceId) && is_numeric($sourceId)) {
                if (!isset(self::$sources[$sourceId])) {
                    $source = $this->sourceFactory->create()->load(
                        $sourceId
                    );
                    self::$sources[$sourceId] = $source;
                } else {
                    $source = self::$sources[$sourceId];
                }
                if ($source->getId()) {
                    $sourceText .= $source->getName() . " (" . $this->sourceType->getName(
                            $source->getType()
                        ) . ")<br>";
                }
            }
        }
        return $sourceText;
    }
}
