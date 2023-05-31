<?php

namespace Icube\CustomCustomer\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class Option extends AbstractFieldArray
{

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('code', ['label' => __('Code'), 'class' => 'required-entry']);
        $this->addColumn('jenis_usaha', ['label' => __('Jenis Usaha'), 'class' => 'required-entry']);
        $this->_addAfter = false;
    }
}