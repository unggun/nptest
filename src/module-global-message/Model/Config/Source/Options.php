<?php

namespace Icube\GlobalMessage\Model\Config\Source;

class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
            ['label' => __('Unvalidated'), 'value' => 'unvalidated'],
            ['label' => __('Unverified'), 'value' => 'unverified'],
            ['label' => __('on_progress'), 'value' => 'on_progress'],
            ['label' => __('Verified'), 'value' => 'verified'],
            ['label' => __('Validated'), 'value' => 'validated'],
        ];

        return $this->_options;
    }
}
