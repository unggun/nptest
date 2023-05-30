<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-03-16T19:08:03+00:00
 * File:          app/code/Xtento/OrderImport/Model/Processor/Mapping/AbstractConfiguration.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Processor\Mapping;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Xtento\OrderImport\Model\Log;

abstract class AbstractConfiguration
{
    protected $configurationType;
    protected $field = '';
    protected $xmlConfig = false;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * AbstractConfiguration constructor.
     *
     * @param Registry $frameworkRegistry
     * @param RequestInterface $appRequestInterface
     */
    public function __construct(
        Registry $frameworkRegistry,
        RequestInterface $appRequestInterface
    ) {
        $this->registry = $frameworkRegistry;
        $this->request = $appRequestInterface;

    }

    protected function loadXml($fieldXml)
    {
        if (empty($fieldXml)) {
            return false;
        }
        try {
            $this->xmlConfig = new \DOMDocument();
            $this->xmlConfig->loadXML($fieldXml);
        } catch (\Exception $e) {
            $log = $this->registry->registry('orderimport_log');
            if ($log !== null) {
                $log->setResult(Log::RESULT_WARNING);
                $log->addResultMessage(
                    "Could not load XML configuration for field " . $this->field . ", skipping field validation: " . $e->getMessage(
                    )
                );
            }
            if ($this->request && $this->request->getModuleName() == 'xtento_orderimport'
                && $this->request->getControllerName() == 'profile'
                && $this->request->getActionName() == 'edit'
            ) {
                $this->registry->register(
                    'orderimport_xml_' . $this->configurationType . '_warning',
                    __("Could not load XML configuration for field " . $this->field . ": " . $e->getMessage()),
                    true
                );
            }
            return false;
        }
        return true;
    }

    /**
     * @return \DOMDocument|bool
     */
    protected function getXmlConfig()
    {
        return $this->xmlConfig;
    }

    public function getConfiguration($field, $fieldXml)
    {
        $this->field = $field;
        $fieldConfiguration = [];
        if ($this->loadXml($fieldXml)) {
            $xmlConfig = $this->getXmlConfig();
            $root = $xmlConfig->documentElement;
            $fieldConfiguration = (array)$this->domToArray($root);
            $fieldConfiguration['@root'] = $root->tagName;
        }
        return $fieldConfiguration;
    }

    /**
     * Convert DOMElement to array
     *
     * @param $node
     *
     * @return array|string
     */
    protected function domToArray($node)
    {
        $output = [];
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domToArray($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string)$v;
                    }
                }
                if ($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
                    $output = ['@content' => $output]; //Change output into an array.
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string)$attrNode->value;
                        }
                        $output['@'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) == 1 && $t != '@') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
    }
}