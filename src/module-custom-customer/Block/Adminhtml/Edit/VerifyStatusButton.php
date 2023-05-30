<?php

namespace Icube\CustomCustomer\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\App\ResourceConnection;

/**
 * Class ClearCartButton
 *
 * @package Magento\Customer\Block\Adminhtml\Edit
 */

class VerifyStatusButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context, $registry);
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get button data.
     *
     * @return array
     */
    public function getButtonData()
    {
        $customerId = $this->getCustomerId();

        $data = [];
        if ($customerId) {
            $data = [
                'label' => __('Verify Status'),
                'class' => 'save primary',
                'id' => 'customer-edit-verify-status',
                'data_attribute' => [
                    'url' => $this->verifyStatus(),
                ],
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure to approve this account ?'
                ) . '\', \'' . $this->verifyStatus() . '\', {"data": {}})',
                'sort_order' => 70,
            ];

            $verification_status = $this->getCustomerVerificationStatus();
            if (isset($verification_status) && !is_null($verification_status)) {
                if ($verification_status != 'on_progress') {
                    $data['disabled'] = true;
                } else {
                    $data['disabled'] = false;
                }
            } else {
                $data['disabled'] = true;
            }

        }
        return $data;
    }

    public function verifyStatus()
    {
        return $this->getUrl(
            'updateCustomCustomer/customer/verifyStatus',
            ['id',$this->getCustomerId()]
        );
    }

    public function getCustomerVerificationStatus()
    {
        $connection = $this->resourceConnection->getConnection();
        $customerId = $this->getCustomerId();

        try {
            $query = "
                SELECT
                    cet.value AS verification_status
                FROM customer_entity ce
                    JOIN customer_entity_text cet ON cet.entity_id = ce.entity_id
                    JOIN eav_attribute ea ON ea.attribute_id = cet.attribute_id
                WHERE ea.attribute_code = 'verification_status'
                AND ce.entity_id = $customerId
            ";

            $result = $connection->fetchRow($query);
            return isset($result['verification_status']) ? $result['verification_status'] : null;
        } catch (\Throwable $th) {
            throw $th;
            return null;
        }
    }
}
