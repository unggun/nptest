<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\OtpVerification\Model\Resolver\Otp;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\ScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Icube\SmsOtp\Helper\Otp as OtpHelper;
use Icube\OtpFazpass\Helper\FazpassApi;
use Icube\SmsOtp\Model\OtpFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Request Otp Login resolver
 */
class CheckOtpConfig implements ResolverInterface
{
    /**
     * sms config
     */
    const SMS_TYPE_CONFIG = 'icube_fazpass/general/enable_otp_sms';
    /**
     * wa config
     */
    const WA_TYPE_CONFIG = 'icube_fazpass/general/enable_otp_wa';
    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var OtpHelper
     */
    private $otpHelper;

    /**
     * @var FazpassApi
     */
    private $fazpassApi;

    /**
     * @var OtpFactory
     */
    private $otpFactory;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * RequestOtpLogin constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param OtpHelper $otpHelper
     * @param FazpassApi $fazpassApi
     * @param OtpFactory $otpFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        OtpHelper $otpHelper,
        FazpassApi $fazpassApi,
        OtpFactory $otpFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->otpHelper = $otpHelper;
        $this->fazpassApi = $fazpassApi;
        $this->otpFactory = $otpFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->scopeConfig = $scopeConfig;
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

        $smsConfig = $this->scopeConfig->getValue(self::SMS_TYPE_CONFIG, ScopeInterface::SCOPE_STORE);
        $waConfig = $this->scopeConfig->getValue(self::WA_TYPE_CONFIG, ScopeInterface::SCOPE_STORE);

        if($waConfig == 1 && $smsConfig == 1){
            return ['otp_type'=>["wa","sms"]];
        }else if($waConfig == 1){
            return ['otp_type'=>["wa"]];
        }
        else if($smsConfig == 1){
            return ['otp_type'=>["sms"]];
        } 
        else{
            return ['otp_type'=>[]];
        }
    }
}
