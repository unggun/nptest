<?php

declare(strict_types=1);

namespace Icube\AwpNotification\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Icube\InboxNotification\Model\NotificationFactory;

/**
 * Create customer account resolver
 */
class CustomerNotificationList extends \Icube\NotificationGraphQl\Model\Resolver\CustomerNotificationList
{
    private $_notificationFactory;

    /**
     * CustomerNotificationList constructor.
     *
     * @param \Icube\InboxNotification\Model\NotificationFactory $notificationFactory
     * 
     */
    public function __construct(
        NotificationFactory $notificationFactory
    ) {
        $this->_notificationFactory = $notificationFactory;
    }
    
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $customerId = $context->getUserId();
        $all = $this->_notificationFactory->create()
            ->getCollection()
            ->addFieldToSelect(['entity_id', 'subject', 'created_at','content'])
            ->addExpressionFieldToSelect('unread', 'IF (status = "unread" OR status = 0, 1, 0)', [])
            ->addFieldToFilter('customer_id', array('in'=>array($customerId,0))) // override to allow blast notif
            ->setOrder('created_at', 'desc')
            ->getData();
        
        $totalUnread = 0;
        $items = array();
        if(!empty($all)){
            foreach($all as $data){
                $item['entityId']   = $data['entity_id'];
                $item['subject']    = $data['subject'];
                $item['createdAt']  = $data['created_at'];
                $item['unread']     = $data['unread'];
                $item['content']     = $data['content'];
                array_push($items,$item);
                if($data['unread'] || $data['unread'] == 'unread') {
                    $totalUnread++;
                } 
            }
        }

        $result['items'] = $items;
        $result['totalUnread']  = $totalUnread;
        return $result;
	}
}
