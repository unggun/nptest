<?php

namespace Icube\OverrideAdvancerate\Ui\Component\Listing\Column;

use Magento\Customer\Model\ResourceModel\Group\Collection;

class CustomerGroup implements \Magento\Framework\Option\ArrayInterface
{
    protected $_storeManager;

    public function __construct(
        Collection $collection
    ) {
        $this->customerGroups = $collection;
    }

    public function toOptionArray()
    {
        $customerGroups = $this->customerGroups->toOptionArray();
        $options = [];

        foreach ($customerGroups as $group) {
            $options[] = ['value' => $group['value'], 'label' => $group['label']];
        }

        return $options;
    }
}
