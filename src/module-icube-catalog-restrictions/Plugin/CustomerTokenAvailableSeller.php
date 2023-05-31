<?php

namespace Icube\CatalogRestrictions\Plugin;

use Icube\SmsOtpGraphQl\Model\Resolver\GenerateCustomerTokenCustom;
use Icube\CatalogRestrictions\Helper\Data as DataHelper;

class CustomerTokenAvailableSeller
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
     * @param GenerateCustomerTokenCustom $subject
     * @param mixed $result
     * @return array
     */
    public function afterResolve(GenerateCustomerTokenCustom $subject, $result): array
    {
        $token = $result['token'];
        $availableIds = $this->helper->getAvailableSellerByToken($token);

        $result['available_seller'] = array_unique($availableIds);

        return $result;
    }
}
