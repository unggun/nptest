<?php

namespace Icube\CustomCustomer\Controller\Adminhtml\Customer;

use Magento\Framework\App\ResourceConnection;

class VerifyStatus extends \Magento\Backend\App\Action
{
    /**
    * @param \Magento\Framework\App\Action\Context $context
    */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ResourceConnection $resourceConnection
    ){
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }
    /**
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $request = $this->getRequest();
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerId = (int)$request->getParams()[1];

        if ($customerId) {
            try {
                $query = "
                    UPDATE customer_entity_text cet
                        LEFT JOIN customer_entity ce ON ce.entity_id = cet.entity_id
                        LEFT JOIN eav_attribute ea ON ea.attribute_id = cet.attribute_id
                    SET cet.value = 'validated'
                    WHERE ea.attribute_code = 'verification_status'
                    AND ce.entity_id = $customerId
                ";
                $connection->query($query);
            } catch (\Throwable $th) {
                throw $th;
            }
        }

        return $resultRedirect->setPath('customer/index/edit/id/'.$customerId);
    }
}