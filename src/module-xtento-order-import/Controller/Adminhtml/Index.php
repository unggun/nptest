<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-05T12:16:28+00:00
 * File:          app/code/Xtento/OrderImport/Controller/Adminhtml/Index.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Controller\Adminhtml;

abstract class Index extends \Xtento\OrderImport\Controller\Adminhtml\Action
{
    /**
     * Check if user has enough privileges
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * @param $resultPage \Magento\Backend\Model\View\Result\Page
     */
    protected function updateMenu($resultPage)
    {
        $resultPage->setActiveMenu('Xtento_OrderImport::profiles');
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Order Import'), __('Order Import'));
        $resultPage->getConfig()->getTitle()->prepend(__('Order Import'));
    }
}
