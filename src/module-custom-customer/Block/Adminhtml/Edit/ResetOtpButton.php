<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Icube\SmsOtp\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;

/**
 * Class ResetOtpButton
 *
 * @package Magento\Customer\Block\Adminhtml\Edit
 */
class ResetOtpButton extends GenericButton implements ButtonProviderInterface
{
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
            $deleteConfirmMsg = __("Are you sure you want to reset the customer's OTP?");
            $data = [
                'label' => __('Reset OTP Customer'),
                'class' => 'resetOtp',
                'id' => 'customer-edit-reset-otp-button',
                'data_attribute' => [
                    'url' => $this->ResetOtp(),
                ],
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->ResetOtp() . '\', {"data": {}})',
                'sort_order' => 20,
                'aclResource' => 'Icube_CustomCustomer::resetOtp',
            ];
        }
        return $data;
    }

    /**
     * Get invalidate token url.
     *
     * @return string
     */
    public function ResetOtp()
    {
        return $this->getUrl('CustomCustomer/customer/resetOtp', ['id' => $this->getCustomerId()]);
    }
}
