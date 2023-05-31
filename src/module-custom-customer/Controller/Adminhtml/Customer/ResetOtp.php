<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Icube\CustomCustomer\Controller\Adminhtml\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * ResetOtp customer action.
 */
class ResetOtp extends \Icube\SmsOtp\Controller\Adminhtml\Customer\ResetOtp
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Icube_CustomCustomer::resetOtp';
    /**
     * ResetOtp customer action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerId = $this->initCurrentCustomer();
        if (!empty($customerId)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();

            $sql = 'SELECT * FROM customer_entity WHERE entity_id = '.$customerId;
            $result = $connection->fetchAll($sql);
            $customerTelephone = $result[0]['telephone'];
            $customerWA = $result[0]['whatsapp_number'];
            try {
                if(!empty($customerTelephone)){
                    $sql = 'DELETE FROM icube_sms_otp WHERE number_phone = ' .$customerTelephone;
                    $connection->query($sql);

                    $sql = 'DELETE FROM icube_sms_otp WHERE number_phone = ' .$customerWA;
                    $connection->query($sql);
                    $this->messageManager->addSuccessMessage(__('You Reset Otp the customer.')); 
                }else{
                    $this->messageManager->addErrorMessage(__('Empty the customer telephone value.'));
                }
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('customer/index');
    }
}
