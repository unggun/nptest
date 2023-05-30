<?php

namespace Icube\CustomCustomer\Plugin\Oauth;

class Token
{
    public function beforeCreateCustomerToken($subject, $userId)
    {
        $subject->setCreatedAt(date('Y-m-d H:i:s', strtotime("+10 Years")));
        return [$userId];
    }
}
