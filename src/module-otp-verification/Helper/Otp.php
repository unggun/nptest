<?php
namespace Icube\OtpVerification\Helper;

use Icube\SmsOtp\Helper\Otp as OtpHelper;

class Otp extends OtpHelper
{
    protected $scopeConfig;
    protected $registry;
    protected $otpCollectionFactory;
    protected $_resource;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        \Icube\SmsOtp\Model\ResourceModel\Otp\CollectionFactory $otpCollectionFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $CustomerCollectionFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->registry = $registry;
        $this->otpCollectionFactory = $otpCollectionFactory;
        $this->_resource = $resource;
        $this->CustomerCollectionFactory = $CustomerCollectionFactory;
        $this->customerSession = $customerSession;
    }

    public function isEnableOtpChangePhonenumber()
    {
        $enableValue = $this->scopeConfig->getValue(
            'icube_otp_verification/enable_otp/change_phonenumber',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($enableValue == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getExpiredTimeChangePhonenumber()
    {
        return (int)$this->scopeConfig->getValue(
            'icube_otp_verification/expired_time/change_phonenumber',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getMaxTryChangePhonenumber()
    {
        return (int)$this->scopeConfig->getValue(
            'icube_otp_verification/max_try/change_phonenumber',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getLengthOtpChangePhonenumber()
    {
        return (int)$this->scopeConfig->getValue(
            'icube_otp_verification/length_otp/change_phonenumber',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function formatedPhoneNumber($phoneNumber)
    {
        $pattern = "/^(\+62)/";
        $isFirstCharNotNumber = preg_match($pattern, $phoneNumber ?? "");

        if ($isFirstCharNotNumber) {
            $phoneNumber = preg_replace($pattern, "0", $phoneNumber);
        }

        return $phoneNumber;
    }

    public function isValidOtp($phoneNumber, $otpCode, $type)
    {
        $otpCollection = $this->otpCollectionFactory->create();
        $otpCollection->addFieldToFilter('number_phone', ['eq' => $phoneNumber])
        ->addFieldToFilter('otp_code', ['eq' => $otpCode])
        ->addFieldToFilter('type', ['eq' => $type])
        ->load();
        $otp = $otpCollection->getFirstItem()->getData();

        $isValidOtp = false;
        if (!empty($otp)) {
            $otpTime = new \DateTime(@$otp["updated_at"]);
            $seconds = (int)@$otp["expired_time"];
            $expiredTime = clone $otpTime;
            $expiredTime->add(new \DateInterval("PT".$seconds."S"));
            $now = new \DateTime();
            if ($now < $expiredTime) {
                $isValidOtp = true;
            }
        }

        return $isValidOtp;
    }

    public function getCustPhoneNumber()
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $customerCollection = $this->CustomerCollectionFactory->create();
        $customerCollection->getSelect()->where("entity_id = '".$customerId."'");
        $firstCustomer = $customerCollection->getFirstItem();
        $telephone = $firstCustomer->getData("telephone");

        return $telephone;
    }
}