<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2019-09-09T11:00:25+00:00
 * File:          app/code/Xtento/OrderImport/Block/Adminhtml/Profile/Edit/Tab/Automatic.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Block\Adminhtml\Profile\Edit\Tab;

class Automatic extends \Xtento\OrderImport\Block\Adminhtml\Widget\Tab implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesNo;

    /**
     * @var \Xtento\XtCore\Helper\Cron
     */
    protected $cronHelper;

    /**
     * @var \Xtento\OrderImport\Model\System\Config\Source\Cron\Frequency
     */
    protected $cronFrequency;

    /**
     * Automatic constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Xtento\XtCore\Helper\Cron $cronHelper
     * @param \Magento\Config\Model\Config\Source\Yesno $yesNo
     * @param \Xtento\OrderImport\Model\System\Config\Source\Cron\Frequency $cronFrequency
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Xtento\XtCore\Helper\Cron $cronHelper,
        \Magento\Config\Model\Config\Source\Yesno $yesNo,
        \Xtento\OrderImport\Model\System\Config\Source\Cron\Frequency $cronFrequency,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->cronFrequency = $cronFrequency;
        $this->cronHelper = $cronHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('orderimport_profile');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'cronjob_fieldset',
            [
                'legend' => __('Cronjob Import'),
            ]
        );

        $fieldset->addField(
            'cronjob_note',
            'note',
            [
                'text' => __(
                    '<strong>Important</strong>: To use cron job imports, please make sure the Magento cronjob has been set up as explained <a href="https://support.xtento.com/wiki/Setting_up_the_Magento_cronjob_(Magento_2)" target="_blank">here</a>.'
                )
            ]
        );


        if ($this->cronHelper->isCronRunning()) {
            $model->setCronjobStatus(
                __(
                    "Cron seems to be running properly. Seconds since last execution: %1",
                    (time() - $this->cronHelper->getLastCronExecution())
                )
            );
            $note = '';
        } else {
            if ((time() - $this->cronHelper->getInstallationDate(
                    )) > (60 * 30)
            ) { // Module was not installed within the last 30 minutes
                if ($this->cronHelper->getLastCronExecution() == '') {
                    $model->setCronjobStatus(
                        __("Cron doesn't seem to be set up at all. Cron did not execute within the last 15 minutes.")
                    );
                    $note = __(
                        'Please make sure to set up the cronjob as explained <a href="https://support.xtento.com/wiki/Setting_up_the_Magento_cronjob_(Magento_2)" target="_blank">here</a> and check the cron status 15 minutes after setting up the cronjob properly again.'
                    );
                } else {
                    $model->setCronjobStatus(
                        __(
                            'Cron doesn\'t seem to be set up properly. Cron did not execute within the last 15 minutes. Last execution was %1 seconds ago.',
                            (time() - $this->cronHelper->getLastCronExecution())
                        )
                    );
                    $note = __(
                        'Please make sure to set up the cronjob as explained <a href="https://support.xtento.com/wiki/Setting_up_the_Magento_cronjob_(Magento_2)" target="_blank">here</a> and check the cron status 15 minutes after setting up the cronjob properly again.'
                    );
                }
            } else {
                $model->setCronjobStatus(__("Cron status wasn't checked yet. Please check back in 30 minutes."));
                $note = __(
                    'Please make sure to set up the cronjob as explained <a href="https://support.xtento.com/wiki/Setting_up_the_Magento_cronjob_(Magento_2)" target="_blank">here</a> and check the cron status 15 minutes after setting up the cronjob properly again.'
                );
            }
        }
        $fieldset->addField(
            'cronjob_status',
            'text',
            [
                'label' => __('Cronjob Status'),
                'name' => 'cronjob_status',
                'disabled' => true,
                'note' => $note,
                'value' => $model->getCronjobStatus()
            ]
        );

        $fieldset->addField(
            'cronjob_enabled',
            'select',
            [
                'label' => __('Enable Cronjob Import'),
                'name' => 'cronjob_enabled',
                'values' => $this->yesNo->toOptionArray()
            ]
        );

        $fieldset->addField(
            'cronjob_frequency',
            'select',
            [
                'label' => __('Import Frequency'),
                'name' => 'cronjob_frequency',
                'values' => $this->cronFrequency->toOptionArray(),
                'note' => __('How often should the import be executed?')
            ]
        );

        $fieldset->addField(
            'cronjob_custom_frequency',
            'text',
            [
                'label' => __('Custom Import Frequency'),
                'name' => 'cronjob_custom_frequency',
                'note' => __(
                    'A custom cron expression can be entered here. Make sure to set "Cronjob Frequency" to "Use custom frequency" if you want to enter a custom cron expression here. To set up multiple cronjobs, separate multiple cron expressions by a semi-colon ; Example: */5 * * * *;0 3 * * * '
                ),
                'class' => 'validate-cron',
                'after_element_html' => $this->getCronValidatorJs()
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getCronValidatorJs()
    {
        $errorMsg = __('This is no valid cron expression.');
        $js = <<<EOT
<script>
require(['jquery', 'mage/backend/validation'], function ($) {
    jQuery.validator.addMethod('validate-cron', function(v, e) {
         if (v == "") {
            return true;
         }
         return RegExp("^[-0-9,*/; ]+$","gi").test(v);
    }, '{$errorMsg}');
});
</script>
EOT;

        return $js;
    }

    /**
     * Prepare label for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Automatic Import');
    }

    /**
     * Prepare title for tab
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Automatic Import');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}