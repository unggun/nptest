<?php
namespace Icube\AwpNotification\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Customer\Model\EmailNotification;

class SendEmail extends \Icube\QueueNotification\Console\Command\SendEmail
{

  protected $scheduleDataFactory;
  protected $helper;

  public function __construct(
    \Icube\EventNotification\Model\Email\TransportBuilder $transportBuilder,
    \Icube\QueueNotification\Model\EmailQueue $emailQueueModel,
    \Icube\QueueNotification\Model\InboxModelFactory $inboxModel,
    \Magento\Framework\App\State $state,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Icube\Twilio\Helper\TwilioApi $twilioApi,
    \Icube\SircloChat\Helper\SircloChatApi $sircloChatApi,
    \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
    \Icube\AwpNotification\Model\ScheduleDataFactory $scheduleDataFactory,
    \Icube\PushNotificationFirebase\Helper\Config $helper,
  )
  {
    parent::__construct($transportBuilder,$emailQueueModel,$inboxModel,$state,$scopeConfig,$twilioApi,$sircloChatApi,$shipmentRepository);
    $this->scheduleDataFactory = $scheduleDataFactory;
    $this->helper = $helper;
  }
  
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      try {
          $this->state->getAreaCode();
      } catch (\Magento\Framework\Exception\LocalizedException $e) {
          $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
      }
       
      $collection = $this->emailQueueModel->getCollection()->addFieldToFilter('status','0');
      $sendername = $this->getStorename();
      $senderemail = $this->getStoreEmail();

      $matchStore = '';
      foreach ($collection as $key => $queue) {
        if($queue->getType() == 'email') {
          if ($queue->getStoreId() == $matchStore || $matchStore == '') {
            try{
                $queue->setStatus(2);
                $queue->save();
                $from = [
                  'name' => $sendername,
                  'email' => $senderemail
                ];
                $templateOptions = [
                  'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                  'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ];
                $templateVars = (!is_array(unserialize((string) $queue->getParams()))) ? [] : unserialize((string) $queue->getParams());

                // notes : notes : template default is mandatory, please change mail body by call prepareMessageEmail
                $defaultTemplateIdentifier = $this->getDefaultTemplateId();

                if($this->isBcc() == true){
                  $transport = $this->transportBuilder->setTemplateIdentifier($defaultTemplateIdentifier)
                  ->setTemplateOptions($templateOptions)
                  ->setTemplateVars($templateVars)
                  ->setFrom($from)
                  ->addBcc($this->emailBcc())
                  ->addTo($queue->getEmail(), $queue->getCustomerName())
                  ->prepareMessageEmail($queue->getContent(), 'text/html', $queue->getSubject())
                  ->getMailTransport();
                } else {
                  $transport = $this->transportBuilder->setTemplateIdentifier($defaultTemplateIdentifier)
                    ->setTemplateOptions($templateOptions)
                    ->setTemplateVars($templateVars)
                    ->setFrom($from)
                    ->addTo($queue->getEmail(), $queue->getCustomerName())
                    ->prepareMessageEmail($queue->getContent(), 'text/html', $queue->getSubject())
                    ->getMailTransport();
                }
                  $transport->sendQueue($queue->getStoreId());
                  $queue->setStatus(1);
                  $queue->save();
                  $output->writeln('[Icube_QueueNotification] - Email send to '.$queue->getEmail());
                  $matchStore = $queue->getStoreId();
            } catch (\Exception $e){
                echo $e->getMessage();
                $output->writeln('<error>[Icube_QueueNotification] - '.$e->getMessage().'</error>');
                        $output->writeln('<error>[Icube_QueueNotification] - Send email failed</error>');
                $queue->setStatus(3);
                $queue->save();
            }
          }
        } else if ($queue->getType()=='inbox') {
          
          $timezone = new \DateTimeZone($this->helper->getTimezoneLocaleValue());
          $date = new \DateTime();
          $date->setTimeZone($timezone);

          $scheduleData = $this->scheduleDataFactory->create();
          $scheduleData->setTopic($queue->getSubject());
          $scheduleData->setTitle($queue->getSubject());
          $scheduleData->setDesc($queue->getContent());
          $scheduleData->setCustomerId($queue->getCustomerId());
          $scheduleData->setSchedule($date->format('Y-m-d H:i:s'));
          $scheduleData->save();

          $inboxModel = $this->inboxModel->create();
          $inboxModel->setData([
                      'email' => $queue->getEmail(),
                      'subject' => $queue->getSubject(),
                      'content' => $queue->getContent(),
                      'customer_name' => $queue->getCustomerName(),
                      'store_id' => $queue->getStoreId(),
                      'status' => '0',
                      'customer_id' => $queue->getCustomerId(),
                  ]);
          $inboxModel->save();
          $queue->setStatus(1);   
          $queue->save();
          $output->writeln('[Icube_QueueNotification] - Inbox success for '.$queue->getEmail());
        } else if ($queue->getType()=='sms') {
            if ($queue->getPhoneNumber() !== NULL && $queue->getPhoneNumber() !== '') {
                $sendMessage = $this->twilioApi->sendSms($queue->getContent(), $queue->getPhoneNumber());
                $sendMessage = json_decode($sendMessage, true);
                if ($sendMessage['status'] !== 400 ) {
                    $queue->setStatus(1);   
                    $queue->save();
                    $output->writeln('[Icube_QueueNotification] - SMS success for '.$queue->getEmail());
                } else {
                    $queue->setStatus(2);   
                    $queue->save();
                    $output->writeln('<error>[Icube_QueueNotification] - SMS Failed</error>');
                }
            } else {
                $queue->setStatus(3);   
                $queue->save();
            }
        } else if ($queue->getType() == 'sirclochat') {
          $sendNotif = true;
          $event = $queue->getEvent();
          if ($event == 'sales_email_shipment_template' || $event == 'sales_email_shipment_guest_template') {
            $sendNotif = false;
            $shipment = json_decode($queue->getContent(), true);
            $shipmentRepo = $this->shipmentRepository->get($shipment['shipment_id']);
            if (!empty($shipmentRepo->getTracks())) {
              $sendNotif = true;
            }
          }

          if ($sendNotif) {
            if ($queue->getPhoneNumber() !== NULL && $queue->getPhoneNumber() !== '')
            {
              $phone_sub = $queue->getPhoneNumber();
              if (substr($phone_sub, 0, 3) == '+62') {
                $phone_number = substr($phone_sub, 1);
              } elseif (substr($phone_sub, 0, 1) == '0') {
                $cutTelephone = substr($phone_sub, 1);
                $phone_number = '62'.$cutTelephone;
              } elseif (substr($phone_sub, 0, 1) == '8') {
                $cutTelephone = substr($phone_sub, 1);
                $phone_number = '628'.$cutTelephone;
              } else {
                $phone_number = $phone_sub;
              }
              $content = $queue->getContent();
              $templateId = $queue->getEvent();
              $sendChat = $this->sircloChatApi->sendChat($content, $phone_number, $templateId, $queue->getStoreId());
              $sendChat = json_decode($sendChat, true);
              if ($sendChat['status'] == 200)
              {
                $queue->setStatus(1);   
                $queue->save();
                $output->writeln('[Icube_QueueNotification] - Sirclo Chat success for '.$queue->getPhoneNumber());
              }
              else
              {
                $queue->setStatus(2);   
                $queue->save();
                $output->writeln('<error>[Icube_QueueNotification] - Sirclo Chat Failed</error>');
              }
            } 
            else
            {
              $queue->setStatus(3);   
              $queue->save();
            }
          }
        }
      }
    }

}