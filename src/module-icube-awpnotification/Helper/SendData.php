<?php

namespace Icube\AwpNotification\Helper;

use \Magento\Framework\App\Helper\Context;
use \Icube\PushNotificationFirebase\Helper\Config;
use Magento\Store\Model\StoreManagerInterface;

class SendData extends \Icube\PushNotificationFirebase\Helper\sendData
{
    protected $scheduleDataFactory;
    protected $subscriberDataFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Icube\PushNotificationFirebase\Helper\Config $helper,
        \Icube\PushNotificationFirebase\Model\ResourceModel\MessageData\CollectionFactory $messageDataFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Icube\AwpNotification\Model\ResourceModel\ScheduleData\CollectionFactory $scheduleDataFactory,
        \Icube\AwpNotification\Model\ResourceModel\SubscriberData\CollectionFactory $subscriberDataFactory
        )
    {
        parent::__construct($context,$date,$helper,$messageDataFactory,$storeManager,$scopeConfig);
        $this->scheduleDataFactory = $scheduleDataFactory;
        $this->subscriberDataFactory = $subscriberDataFactory;
    }

   public function sendData($output)
   {
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $enable = $this->helper->getEnabledValue();  

        $timezone = new \DateTimeZone($this->helper->getTimezoneLocaleValue());
        $date = new \DateTime();
        $date->setTimeZone($timezone);

        $collection = $this->scheduleDataFactory->create()
                        ->addFieldToFilter('schedule',['lteq' => $date->format('Y-m-d H:i:s')])
                        ->addFieldToFilter('status',['in' => array(0,3)]);

        foreach($collection as $data){
            if($data->getCustomerId() == 0) // blast
            {
                $Imageurl = '';
                $Logourl = '';
                if($data->getImage()){
                    $Imageurl = $storeUrl."media/".$data->getImage();
                }

                if($data->getLogo()){
                    $Logourl = $storeUrl."media/".$data->getLogo();
                }
                
                
                $dataSend = array(
                    'to' => $this->helper->getTopicValue(),
                    'data' => array(
                        'title' => $data->getTitle(),
                        'body' => $data->getDesc(),
                        'description' => $data->getDesc(),
                        'image' => $Imageurl,
                        'logo' => $Logourl,
                        'type' => 'Product',
                        'path' => $data->getPath()
                    ),
                    'notification' => array(
                        'body' =>$data->getDesc(),
                        'image' => $Imageurl,
                        'title' => $data->getTitle(),
                        'url' => $data->getPath(),
                        'show_in_foreground' => true
                    )
                );

            }else{
                $subsriberColl = $this->subscriberDataFactory->create()->addFieldToFilter('customer_id',$data->getCustomerId());
                $registration_ids = array();
                foreach($subsriberColl as $subscriber){
                    $registration_ids[] = $subscriber->getToken();
                }

                $dataSend = array(
                    "registration_ids" => $registration_ids,
                    'notification' => array(
                        'body' =>$data->getDesc(),
                        'title' => $data->getTitle()
                    ),
                    'data' => array(
                        'url' => $data->getPath()
                    )
                );
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($dataSend),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: key=".$this->helper->getAuthValue().""
            ),
            ));
            
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl,CURLINFO_HTTP_CODE);

            $output->writeln("[Icube_AWPNotification] send - ".json_encode($dataSend));
            $output->writeln("[Icube_AWPNotification] response - ".json_encode($response));

            if($httpcode == 200){
                $data->setStatus(1)->save();
            }else{
                $data->setStatus(3)->save();
            }
        }
    }
} 
