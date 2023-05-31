<?php
namespace Icube\AwpNotification\Observer;

class HideFields implements \Magento\Framework\Event\ObserverInterface
{
    protected $_request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ){
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $acionName = $this->_request->getFullActionName();

        $ruleId = $this->_request->getParam('entity_id');

        if($acionName === 'icube_pushnotificationfirebase_message_add')
        {
            $block = $observer->getEvent()->getBlock();
            if (!isset($block))
                return $this;

            if ($block->getType() == 'Icube\PushNotificationFirebase\Block\Adminhtml\Firebase\Edit\Form\Interceptor') 
            {
                $form = $block->getForm();
                $filedArray = array();

                $fieldset = $form->getElement('base_fieldset');
                $fieldset->removeField('type');
                $fieldset->removeField('desc');
                $form->getElement('path')->setLabel('Url');
            }
        }   

        return $this;
    }
}