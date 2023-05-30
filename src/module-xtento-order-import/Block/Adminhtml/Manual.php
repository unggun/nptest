<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Manual.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml;

class Manual extends \Magento\Backend\Block\Template
{
    protected $importResult = false;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Import\Profile
     */
    protected $profileSource;

    /**
     * @var \Xtento\OrderImport\Helper\Entity
     */
    protected $entityHelper;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @var \Magento\Framework\View\Element\Html\Date
     */
    protected $dateElement;

    /**
     * @var \Xtento\OrderImport\Model\ImportFactory
     */
    protected $importFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface
     */
    protected $dateTimeFormatter;

    /**
     * Manual constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Xtento\OrderImport\Model\System\Config\Source\Import\Profile $profileSource
     * @param \Xtento\OrderImport\Helper\Entity $entityHelper
     * @param \Magento\Framework\View\Element\Html\Date $dateElement
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface $dateTimeFormatter
     * @param \Xtento\OrderImport\Model\ImportFactory $importFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Store\Model\System\Store $systemStore,
        \Xtento\OrderImport\Model\System\Config\Source\Import\Profile $profileSource,
        \Xtento\OrderImport\Helper\Entity $entityHelper,
        \Magento\Framework\View\Element\Html\Date $dateElement,
        \Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface $dateTimeFormatter,
        \Xtento\OrderImport\Model\ImportFactory $importFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->profileSource = $profileSource;
        $this->entityHelper = $entityHelper;
        $this->systemStore = $systemStore;
        $this->dateElement = $dateElement;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->importFactory = $importFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Xtento_OrderImport::manual_import.phtml');
    }

    public function getJs($filename)
    {
        $url = $this->_assetRepo->createAsset(
            'Xtento_OrderImport::js/' . $filename,
            ['_secure' => $this->getRequest()->isSecure()]
        )->getUrl();
        return $url;
    }

    public function getProfileSelectorHtml()
    {
        $html = '<select class="select" name="profile_id" id="profile_id" style="width: 320px;">';
        $html .= '<option value="">' . __('--- Select Profile---') . '</option>';
        $enabledProfiles = $this->profileSource->toOptionArray();
        $profilesByGroup = [];
        foreach ($enabledProfiles as $profile) {
            $profilesByGroup[$profile['entity']][] = $profile;
        }
        foreach ($profilesByGroup as $entity => $profiles) {
            $html .= '<optgroup label="' . __(
                    '%1 Import',
                    $this->entityHelper->getEntityName($entity)
                ) . '">';
            foreach ($profiles as $profile) {
                $html .= '<option value="' . $profile['value'] . '" entity="' . $entity . '">' . $profile['label'] . ' (' . __(
                        'ID: %1',
                        $profile['value']
                    ) . ')</option>';
            }
            $html .= '</optgroup>';
        }
        $html .= '</select>';
        return $html;
    }

    public function getSession()
    {
        return $this->_backendSession;
    }

    public function getImportResult()
    {
        if (!$this->importResult) {
            $this->importResult = $this->getSession()->getData('xtento_orderimport_debug_log');
            $this->getSession()->setData('xtento_orderimport_debug_log', false);
        }
        if (empty($this->importResult)) {
            return false;
        }
        return $this->importResult;
    }

    protected function _toHtml()
    {
        $messagesBlock = <<<EOT
<div id="messages">
    <div class="messages">
        <div class="message message-warning warning" id="warning-msg" style="display:none">
            <div id="warning-msg-text"></div>
        </div>
        <div class="message message-success success" id="success-msg" style="display:none">
            <div id="success-msg-text"></div>
        </div>
    </div>
</div>
EOT;
        return $messagesBlock . parent::_toHtml();
    }
}
