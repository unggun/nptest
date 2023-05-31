<?php
namespace Icube\AwpNotification\Controller\Adminhtml\Message;
 
    
class Save extends \Icube\PushNotificationFirebase\Controller\Adminhtml\Message\Save
{
    protected $scheduleDataFactory;
    protected $resourceConnection;
    protected $notificationFactory;
    protected $groupModel;
    protected $storeManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Icube\PushNotificationFirebase\Model\MessageDataFactory $messageDataFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $adapterFactory,
        \Icube\PushNotificationFirebase\Helper\Config $helper,
        \Icube\AwpNotification\Model\ScheduleDataFactory $scheduleDataFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Icube\InboxNotification\Model\NotificationFactory $notificationFactory,
        \Magento\Customer\Model\Group $groupModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context,$dateFilter,$messageDataFactory,$fileUploaderFactory,$filesystem,$adapterFactory,$helper);
        $this->scheduleDataFactory = $scheduleDataFactory;
        $this->resourceConnection = $resourceConnection;
        $this->notificationFactory = $notificationFactory;
        $this->groupModel = $groupModel;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->_redirect('icube_pushnotificationfirebase/message/add');
            return;
        }


        try {
            $rowData = $this->messageDataFactory->create();
            $rowData->setData($data);
            if (isset($data['entity_id'])) {
                $rowData->setEntityId($data['entity_id']);
            }
            $logoPost = $this->getRequest()->getFiles('logo');
            $fileLogoName = ($logoPost && array_key_exists('name', $logoPost)) ? $logoPost['name'] : null;
            $imagePost = $this->getRequest()->getFiles('image');
            $fileImageName = ($logoPost && array_key_exists('name', $imagePost)) ? $imagePost['name'] : null;
            $storeUrl = $this->storeManager->getStore()->getBaseUrl();
            
            if($rowData->getLogo())
            {
                $logoName = $rowData->getLogo()['value'];
                $rowData->setLogo($logoName);
            }
            if($rowData->getImage())
            {
                $imageName = $rowData->getImage()['value'];
                $rowData->setImage($imageName);
            }
            if($logoPost && $fileLogoName)
            {
                $uploadLogo = $this->fileUploaderFactory->create(['fileId' => 'logo']);
                
                $uploadLogo->setAllowedExtensions(['jpg']);
                $imageAdapter = $this->adapterFactory->create();
                $uploadLogo->addValidateCallback('logo', $imageAdapter, 'validateUploadFile');
                $uploadLogo->setAllowRenameFiles(false);
                
                $uploadLogo->setFilesDispersion(false);
        
                $pathLogo = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
                ->getAbsolutePath('firebase/logo/');
                $uploadLogo->save($pathLogo);
                $rowData->setLogo('firebase/logo/'.$uploadLogo->getUploadedFileName().'');
            }
            if($imagePost && $fileImageName)
            {
                $uploadImage = $this->fileUploaderFactory->create(['fileId' => 'image']);
                
                $uploadImage->setAllowedExtensions(['jpg']);
                $imageAdapter = $this->adapterFactory->create();
                $uploadImage->addValidateCallback('image', $imageAdapter, 'validateUploadFile');
                $uploadImage->setAllowRenameFiles(false);
                
                $uploadImage->setFilesDispersion(false);
        
                $pathImage = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
                ->getAbsolutePath('firebase/image/');
                $uploadImage->save($pathImage);
                $rowData->setImage('firebase/image/'.$uploadImage->getUploadedFileName().'');
            }


            $rowData->save();
            $isTemplateNotExist = true;
            if (!isset($data['entity_id'])) {
                
                if($data['subs_group'] == 'All'){ //blast
                    $scheduleData = $this->scheduleDataFactory->create();
                    $scheduleData->setSchedule($data['date']);
                    $scheduleData->setTopic($data['title']);
                    $scheduleData->setTitle($data['title']);
                    if($imagePost && $fileImageName)
                        {
                            $scheduleData->setImage('firebase/image/'.$uploadImage->getUploadedFileName().'');
                        }
                        
                        if($logoPost && $fileLogoName)
                        {
                            $scheduleData->setLogo('firebase/logo/'.$uploadLogo->getUploadedFileName().'');
                        }
                    $scheduleData->setDesc($data['short_desc']);
                    $scheduleData->setPath($data['path']);
                    if($isTemplateNotExist){
                        $scheduleData->setTemplateId($rowData->getEntityId());
                    }
                    //save data to icube_inbox_notification
                    $notification = $this->notificationFactory->create();
                    $content = '';
                    if($imagePost && $fileImageName)
                    {
                        $content .= '<img src="'.$storeUrl."media/".$scheduleData->getImage().'"/>';
                    }
                    $content .= '<p>'.$data['short_desc'].'</p>';
                    if($scheduleData->getPath()){
                        $content .= '<a href=“'.$scheduleData->getPath().'“>Detail</a>';
                    }

                    //for blast also send data to inbox notification, with customer id 0
                    $notification->setSubject($data['title']);
                    $notification->setContent($content);
                    $notification->setStoreId(0);
                    $notification->setCustomerId(0);
                    $notification->save();

                    $scheduleData->setInboxId($notification->getEntityId());
                    $scheduleData->save();
                    
                }else{ //specific group
                    $connection = $this->resourceConnection->getConnection();
                    $query = "SELECT entity_id,firstname,lastname,store_id FROM customer_entity WHERE group_id = ".$data['subs_group'];
                    $result = $connection->query($query);
                    $storeUrl = $this->storeManager->getStore()->getBaseUrl();

                    foreach ($result as $dataQuery) {
                        $scheduleData = $this->scheduleDataFactory->create();
                        $scheduleData->setSchedule($data['date']);
                        $scheduleData->setTopic($data['title']);
                        $scheduleData->setCustomerId($dataQuery['entity_id']);
                        $scheduleData->setTitle($data['title']);
                        if($imagePost && $fileImageName)
                        {
                            $scheduleData->setImage('firebase/image/'.$uploadImage->getUploadedFileName().'');
                        }
                        
                        if($logoPost && $fileLogoName)
                        {
                            $scheduleData->setLogo('firebase/logo/'.$uploadLogo->getUploadedFileName().'');
                        }
                        $scheduleData->setDesc($data['short_desc']);
                        $scheduleData->setPath($data['path']);
                        if($isTemplateNotExist){
                            $scheduleData->setTemplateId($rowData->getEntityId());
                        }

                        //save data to icube_inbox_notification
                        $notification = $this->notificationFactory->create();
                        $content = '';
                        if($imagePost && $fileImageName)
                        {
                            $content .= '<img src="'.$storeUrl."media/".$scheduleData->getImage().'"/>';
                        }
                        $content .= '<p>'.$data['short_desc'].'</p>';
                        if($scheduleData->getPath()){
                            $content .= '<a href="'.$scheduleData->getPath().'">Detail</a>';
                        }

                        $notification->setSubject($data['title']);
                        $notification->setContent($content);
                        $notification->setCustomerName($dataQuery['firstname'].' '.$dataQuery['lastname']);
                        $notification->setStoreId($dataQuery['store_id']);
                        $notification->setCustomerId($dataQuery['entity_id']);
                        $notification->save();

                        $scheduleData->setInboxId($notification->getEntityId());
                        $scheduleData->save();
                    }
                }
            }
            else{
                 $scheduleDataCollection = $this->scheduleDataFactory->create()->getCollection()->addFieldToFilter('template_id',$data['entity_id']);

                 foreach($scheduleDataCollection as $scheduleData){
                    $scheduleData->setSchedule($data['date']);
                    $scheduleData->setTopic($data['title']);
                    $scheduleData->setTitle($data['title']);
                    if($imagePost && $fileImageName)
                        {
                            $scheduleData->setImage('firebase/image/'.$uploadImage->getUploadedFileName().'');
                        }
                        
                        if($logoPost && $fileLogoName)
                        {
                            $scheduleData->setLogo('firebase/logo/'.$uploadLogo->getUploadedFileName().'');
                        }
                    $scheduleData->setDesc($data['short_desc']);
                    $scheduleData->setPath($data['path']);
                    $scheduleData->save();

                    if($scheduleData->getInboxId()){
                        $notif = $this->notificationFactory->create()->load($scheduleData->getInboxId());
                        $content = '';
                        if($imagePost && $fileImageName)
                        {
                            $content .= '<img src="'.$storeUrl."media/".$scheduleData->getImage().'"/>';
                        }
                        $content .= '<p>'.$data['short_desc'].'</p>';
                        $notif->setSubject($data['title']);
                        $notif->setContent($content);
                        $notif->save();
                    }
                 }
                 
            }

            
            
            
            $this->messageManager->addSuccess(__('Notification has been successfully saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('icube_pushnotificationfirebase/message/index');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Icube_PushNotificationFirebase::message_save');
    }

}
