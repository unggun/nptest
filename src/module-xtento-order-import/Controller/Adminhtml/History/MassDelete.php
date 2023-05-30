<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-18T16:10:55+00:00
 * File:          app/code/Xtento/OrderImport/Controller/Adminhtml/History/MassDelete.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Controller\Adminhtml\History;

class MassDelete extends \Xtento\OrderImport\Controller\Adminhtml\History
{
    /**
     * Mass delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        $ids = $this->getRequest()->getParam('history');
        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select history entries to delete.'));
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        try {
            foreach ($ids as $id) {
                $model = $this->historyFactory->create();
                $model->load($id);
                $model->delete();
            }
            $this->messageManager->addSuccessMessage(
                __('Total of %1 history entries were successfully deleted.', count($ids))
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect->setPath('*/*/');
        return $resultRedirect;
    }
}