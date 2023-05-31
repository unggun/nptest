<?php

namespace Icube\CatalogRestrictions\Plugin;

use Icube\OtpVerification\Model\Resolver\Otp\GenerateCustomerTokenByPhoneOtp;
use Icube\CatalogRestrictions\Helper\Data as DataHelper;

class CustomerTokenAvailableSellerOtp
{
    protected DataHelper $helper;

    public function __construct(
        DataHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * List available sellers for current customer
     *
     * @param GenerateCustomerTokenByPhoneOtp $subject
     * @param mixed $result
     * @return array
     */
    public function afterResolve(GenerateCustomerTokenByPhoneOtp $subject, $result): array
    {
        $token = $result['token'];
        $availableIds = $this->helper->getAvailableSellerByToken($token);

        $result['available_seller'] = array_unique($availableIds);

        return $result;
    }
}
