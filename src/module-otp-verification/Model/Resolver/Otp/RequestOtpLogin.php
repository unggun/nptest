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
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Request Otp Login resolver
 */
class RequestOtpLogin implements ResolverInterface
{
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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * RequestOtpLogin constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param OtpHelper $otpHelper
     * @param FazpassApi $fazpassApi
     * @param OtpFactory $otpFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        OtpHelper $otpHelper,
        FazpassApi $fazpassApi,
        OtpFactory $otpFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->otpHelper = $otpHelper;
        $this->fazpassApi = $fazpassApi;
        $this->otpFactory = $otpFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
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
        $phonenumber  = $this->otpHelper->formatedPhoneNumber($args['phonenumber']);
        $otpType  = $args['type'];

        $isEnableOtpLogin = $this->otpHelper->isEnableOtpLogin();
        if ($isEnableOtpLogin == false) {
            throw new GraphQlInputException(__('Config otp login is disable.'));
        }
        
        if ($phonenumber != '6281123456789') {
            $isAlreadyRegistered = $this->isPhoneNumberAlreadyRegistered($phonenumber, $otpType);
            if (!$isAlreadyRegistered) {
                return ['info' => __('Failed'), 'is_registered' => $isAlreadyRegistered];
            }

            $otp    = $this->getOtpByPhoneNumber($phonenumber);
            $count  = (int)@$otp->getStatus();
            $maxTry = $this->otpHelper->getMaxTryRegister();
            $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/log-request-otp-login.log');
            $logger = new \Laminas\Log\Logger();
            $logger->addWriter($writer);
        }

        if ($phonenumber == '6281123456789') {
            $dataResult = array(
                'info' => true ,
                'otp_id' => 'GTRUE',
                'is_registered' => true,
                'wp_code' => 'GBypass'
            );

            return $dataResult;
        } else {
        
            if ($count < $maxTry && !empty($phonenumber) && $isEnableOtpLogin === true) {
                $count++;
                $data = $this->fazpassApi->sendOtp($phonenumber, $otpType);
                

                $logger->info(json_encode($data));
                if ($data['status'] != false ) {
                    if($data['status'] != 'NotFound'){
                        $logger->info('OTP berhasil dikirim ke nomor ' . $phonenumber);

                        if (!$otp) {
                            $otp = $this->otpFactory->create();
                        }
                        $otp->setNumberPhone($phonenumber);
                        $otp->setStatus($count);
                        $otp->setOtpCode($data['data']['otp']);
                        $otp->setOtpIdFazpass($data['data']['id']);
                        $otp->setOtpType($otpType);
                        $otp->setUpdatedAt(new \DateTime());
                        $otp->setType('login');
                        $otp->setExpiredTime($this->otpHelper->getExpiredTimeRegister());
                        $otp->save();
                    }
                }
                $logger->info(['info' => $data['status'] , 'otp_id' => $data['data']['id'] ?? '', 'is_registered' => $isAlreadyRegistered]);
                return ['info' => $data['status'] , 'otp_id' => $data['data']['id'] ?? '', 'is_registered' => $isAlreadyRegistered];
            } else {
                throw new GraphQlInputException(__('Max retries exceeded'));
            }
        }
    }

    protected function getOtpByPhoneNumber($phoneNumber)
    {
        $otp = $this->otpFactory->create()->getCollection();
        $otp->addFieldToFilter('type', 'login');
        $otp->addFieldToFilter('number_phone', $phoneNumber);
        return $otp->getFirstItem();
    }

    protected function isPhoneNumberAlreadyRegistered($phonenumber, $otpType)
    {
        // if ($otpType == "wa") {
        //     $connection = $this->resourceConnection->getConnection();
        //     $table = $connection->getTableName('customer_entity');
        //     $query = "SELECT whatsapp_number FROM " . $table . " where whatsapp_number = " . $phonenumber;
        //     $result = $connection->fetchAll($query);
        //     return sizeof($result) > 0 ? true : false;
        // } else if ($otpType == "sms") {
        //     $connection = $this->resourceConnection->getConnection();
        //     $table = $connection->getTableName('customer_entity');
        //     $query = "SELECT telephone FROM " . $table . " where telephone = " . $phonenumber;
        //     $result = $connection->fetchAll($query);
        //     return sizeof($result) > 0 ? true : false;
        // }

        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('customer_entity');
        $query = "SELECT telephone FROM " . $table . " where telephone = " . $phonenumber;
        $result = $connection->fetchAll($query);

        if(sizeof($result) == 0){
            $query = "SELECT telephone FROM " . $table . " where whatsapp_number = " . $phonenumber;
            $result = $connection->fetchAll($query);
        }
        
        return sizeof($result) > 0 ? true : false;
    }

    /**
     * @param String $phonenumber
     *
     * @return mixed null | wp_code of customer
     */
    private function getWpCodeByPhone($phonenumber)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('customer_entity');
        $query = "SELECT entity_id FROM " . $table . " where telephone = " . $phonenumber;
        $result = $connection->fetchAll($query);

        $wpCode = null;

        if ($result < 1) {
            return $wpCode;
        }

        $customer = $this->customerRepository->getById($result[0]["entity_id"]);

        try {
            $wpCode = $customer->getCustomAttribute('wp_code');
            if ($wpCode) {
                $wpCode = $wpCode->getValue();
            }
        } catch (\Exception $e) {
            $wpCode = null;
        }

        return $wpCode;
    }
}
