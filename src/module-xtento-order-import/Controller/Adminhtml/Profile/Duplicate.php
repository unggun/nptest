<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-03-04T14:09:20+00:00
 * File:          app/code/Xtento/OrderImport/Controller/Adminhtml/Profile/Duplicate.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Controller\Adminhtml\Profile;

class Duplicate extends \Xtento\OrderImport\Controller\Adminhtml\Profile
{
    /**
     * Duplicate action
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        $id = (int)$this->getRequest()->getParam('id');
        $model = $this->profileFactory->create();
        $model->load($id);

        if ($id && !$model->getId()) {
            $this->messageManager->addErrorMessage(__('This profile does not exist anymore.'));
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        try {

            $profile = clone $model;
            $profile->setEnabled(0);
            $profile->setId(null);
            $profile->setLastModification(time());
            $profile->setLastExecution(null);
            $profile->save();

            $this->_session->setProfileDuplicated(1);
            $this->messageManager->addSuccessMessage(
                __('The profile has been duplicated. Please make sure to enable it.')
            );
            $resultRedirect->setPath('*/*/edit', ['id' => $profile->getId()]);
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect->setPath('*/*/');
        return $resultRedirect;
    }
}