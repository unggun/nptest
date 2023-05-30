<?php


namespace Icube\AwpNotification\Controller\Adminhtml\Message;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Icube\PushNotificationFirebase\Model\ResourceModel\MessageData\CollectionFactory;

class MassDelete extends \Icube\PushNotificationFirebase\Controller\Adminhtml\Message\MassDelete
{
    protected $scheduleDataFactory;
    protected $notificationFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Icube\AwpNotification\Model\ScheduleDataFactory $scheduleDataFactory,
        \Icube\InboxNotification\Model\NotificationFactory $notificationFactory

    ) {
        parent::__construct($context,$filter,$collectionFactory);
        $this->scheduleDataFactory = $scheduleDataFactory;
        $this->notificationFactory = $notificationFactory;
    }

    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $recordDeleted = 0;
        foreach ($collection->getItems() as $record) {
            $record->getId();

            $scheduleDataCollection = $this->scheduleDataFactory->create()->getCollection()->addFieldToFilter('template_id',$record->getId());

            foreach($scheduleDataCollection as $scheduleData){
                if($scheduleData->getInboxId()){
                    $notificationCollection = $this->notificationFactory->create()->getCollection()->addFieldToFilter('entity_id',$scheduleData->getInboxId());
                    foreach ($notificationCollection as $notification) {
                        $notification->delete();
                    }
                }
                $scheduleData->delete();
            }

            $record->delete();
            $recordDeleted++;
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $recordDeleted));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('icube_pushnotificationfirebase/message/index');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Icube_PushNotificationFirebase::message_delete');
    }
}
