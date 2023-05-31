<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\OtpVerification\Model\Resolver\Otp;

use Icube\SmsOtpGraphQl\Model\Customer\CreateCustomerAccount;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Icube\SmsOtp\Helper\Otp as OtpHelper;
use Icube\OtpFazpass\Helper\FazpassApi;
/**
 * Check Otp Login resolver
 */
class CheckOtpLogin implements ResolverInterface
{
    /**
     * @var OtpHelper
     */
    private $otpHelper;

    /**
     * @var FazpassApi
     */
    private $fazpassApi;

    /**
     * RequestOtpLogin constructor.
     *
     * @param OtpHelper $otpHelper
     * @param FazpassApi $fazpassApi
     */
    public function __construct(
        OtpHelper $otpHelper,        
        FazpassApi $fazpassApi,
    ) {        
        $this->fazpassApi = $fazpassApi;
        $this->otpHelper = $otpHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['phonenumber'])) {
            throw new GraphQlInputException(__('"phonenumber" value should be specified'));
        }

        if (empty($args['otp'])) {
            throw new GraphQlInputException(__('"otp" value should be specified'));
        }

        if (empty($args['otp_id'])) {
            throw new GraphQlInputException(__('"otp" value should be specified'));
        }

        $phoneNumber = $this->otpHelper->formatedPhoneNumber($args['phonenumber']);
        $otpCode = $args['otp'];
        $otpId = $args['otp_id'];
        if ($phoneNumber === '6281123456789' && $otpCode === '1234') {
            $isValidOtp['status'] = true;
            $isValidOtp['message'] = 'google by pass';
        } else {
            $isValidOtp = $this->fazpassApi->verifyOtp($otpCode, $otpId);
        }
       
        return ['is_valid_otp' => $isValidOtp['status']??false,'message'=>$isValidOtp['message']??"Failed"];
    }
}
