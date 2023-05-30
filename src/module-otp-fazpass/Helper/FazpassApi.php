<?php
namespace Icube\OtpFazpass\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class FazpassApi extends AbstractHelper
{
	/**
     * sms config
     */
    const SMS_TYPE_CONFIG = 'icube_fazpass/general/enable_otp_sms';
    /**
     * wa config
     */
    const WA_TYPE_CONFIG = 'icube_fazpass/general/enable_otp_wa';

	protected $scopeConfig;
	protected $storeManager;
	protected $_moduleManager;

	public function __construct(	
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Module\Manager $moduleManager
	)
	{
		$this->scopeConfig = $scopeConfig;
		$this->storeManager = $storeManager;
		$this->_moduleManager = $moduleManager;
	}

	public function getFazpassUrlGenerateOtpEndpoint()
	{
		$url = $this->scopeConfig->getValue('icube_fazpass/general/url_generate_otp_endpoint');
		return $url;
	}

	public function getFazpassUrlVerifyOtpEndpoint()
	{
		$url = $this->scopeConfig->getValue('icube_fazpass/general/url_verify_otp_endpoint');
		return $url;
	}

	public function getFazpassMerchantKey()
	{
		$username = $this->scopeConfig->getValue('icube_fazpass/general/merchant_key');

		return $username;
	}

	public function getFazpassGatewayKeySms()
	{
		$pass = $this->scopeConfig->getValue('icube_fazpass/general/gateway_key_sms');

		return $pass;
	}

	public function getFazpassGatewayKeyWa()
	{
		$pass = $this->scopeConfig->getValue('icube_fazpass/general/gateway_key_wa');

		return $pass;
	}


	public function isFazpassEnable()
	{
		$enableValue = $this->scopeConfig->getValue('icube_fazpass/general/enable');

		if ($enableValue == 1) {
			return true;
		} else {
			return false;
		}
	}
	
	public function isSmsEnable()
	{
		$enableValue = $this->scopeConfig->getValue('icube_fazpass/general/enable_otp_sms');

		if ($enableValue == 1) {
			return true;
		} else {
			return false;
		}
	}
	
	public function isWaEnable()
	{
		$enableValue = $this->scopeConfig->getValue('icube_fazpass/general/enable_otp_wa');

		if ($enableValue == 1) {
			return true;
		} else {
			return false;
		}
	}

	/* return bool */
    public function isModuleEnable()
    {
        return $this->_moduleManager->isEnabled('Icube_OtpFazpass');
    }

	public function sendOtp($to, $otpType)
	{
		$smsConfig = $this->scopeConfig->getValue(self::SMS_TYPE_CONFIG, ScopeInterface::SCOPE_STORE);
        $waConfig = $this->scopeConfig->getValue(self::WA_TYPE_CONFIG, ScopeInterface::SCOPE_STORE);

		$urlGenerateOtp = $this->getFazpassUrlGenerateOtpEndpoint();
		$merchantKey = $this->getFazpassMerchantKey();
		if($otpType == "sms"){
			$gatewayKey = $this->getFazpassGatewayKeySms();
		}else if($otpType == "wa"){
			$gatewayKey = $this->getFazpassGatewayKeyWa();
		}
		
		$data = json_encode([
			"phone" => $to,
			"gateway_key" => $gatewayKey
		]);

		$headers = [
            'Authorization: Bearer '.$merchantKey,
            'Content-Type: application/json',
        ];
		
		$ch = curl_init($urlGenerateOtp);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result, true);
		if($otpType == "wa" && $result == NULL){
			if($smsConfig == 1 && $gatewayKey!= NULL ){
				$sendSms = $this->sendOtp($to, "sms");
				return $sendSms;
			}else{
				return ["status"=> "NotFound"];
			}
		}
		else if($otpType == "sms" && $result == NULL){
			return ["status"=> "NotFound"];
		}
		else if($result['status'] == true){
			$result['status'] = "Success";
			return $result;
		}else{
			$result['status'] = "Failed";
			return ["status"=> false];
		}
	}
	
	public function verifyOtp($otpCode, $otpIdFazpass)
	{
		$urlVerifyOtp = $this->getFazpassUrlVerifyOtpEndpoint();
		$merchantKey = $this->getFazpassMerchantKey();	
		
		$data = json_encode([
			"otp_id" => $otpIdFazpass,
			"otp" => $otpCode
		]);

		$headers = [
            'Authorization: Bearer '.$merchantKey,
            'Content-Type: application/json',
        ];
		
		$ch = curl_init($urlVerifyOtp);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result, true);
		
		if($result['status'] == true){
			return $result;
		}else{
			return ["status"=> false];
		}
		
	}
}
