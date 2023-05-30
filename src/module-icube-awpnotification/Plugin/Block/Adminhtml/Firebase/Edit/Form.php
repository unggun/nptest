<?php

namespace Icube\AwpNotification\Plugin\Block\Adminhtml\Firebase\Edit;

class Form
{
    public function aroundGetFormHtml(
    \Icube\PushNotificationFirebase\Block\Adminhtml\Firebase\Edit\Form $subject,
    \Closure $proceed)
    {
        $form = $subject->getForm();
        if (is_object($form))
        {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $groupOptions = $objectManager->get('\Magento\Customer\Model\ResourceModel\Group\Collection')->toOptionArray();


            $arrayValues[] = array('label' => 'All', 'value' => 'All');

            foreach ($groupOptions as $option) {
                $arrayValues[] = array('label' => $option['label'], 'value' => $option['value']);
            }

            $isExist = $form->getElement('entity_id');

            if($isExist == null){
                $fieldset = $form->addFieldset('group_fieldset',['legend' => __('AWP Subscriber Group'), 'class' => 'fieldset-wide']);
                $fieldset->addField(
                    'subs_group',
                    'select',
                    [
                        'name' => 'subs_group',
                        'label' => 'Subscriber Group',
                        'required' => 'true',
                        'note' => __("Select the group"),
                        'value' => 'All',
                        'values' => $arrayValues
                    ]
                );
            }

            

            $subject->setForm($form);
        }

        return $proceed();
    }
}
