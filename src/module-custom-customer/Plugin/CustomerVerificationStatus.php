<?php

namespace Icube\CustomCustomer\Plugin;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerVerificationStatus
{
    const VERIFICATION_STATUS_CODE = 'verification_status';

    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    protected $eavConfig;

    public function __construct(
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Set verification status mapping
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $customer
     * @param string $hash
     * @param string $redirectUrl
     * @return array
     */
    public function beforeCreateAccountWithPasswordHash(
        AccountManagementInterface $subject,
        CustomerInterface $customer,
        $hash,
        $redirectUrl
    ) {
        $statusJds = $customer->getCustomAttribute("status_jds");
        if ($statusJds) {

            $statusJds = strtolower($statusJds->getValue());
            $verificationLabel = '';

            $attribute = $this->eavConfig->getAttribute('customer', self::VERIFICATION_STATUS_CODE);
            $options = $attribute->getSource()->getAllOptions();

            switch ($statusJds) {
                case 'registered':
                    $verificationLabel = 'Unverified';
                    break;
                case 'data_validation':
                    $verificationLabel = 'on_progress';
                    break;
                case 'validated':
                    $verificationLabel = 'Validated';
                    break;
                case 'data_validation_failed':
                    $verificationLabel = 'Unvalidated';
                    break;
                default:
                    break;
            }

            $optionKey = array_search($verificationLabel, array_column($options, 'label'));

            $customer->setCustomAttribute(self::VERIFICATION_STATUS_CODE, $options[$optionKey]['value'] ?? '');
        }
        return [$customer, $hash, $redirectUrl];
    }
}
