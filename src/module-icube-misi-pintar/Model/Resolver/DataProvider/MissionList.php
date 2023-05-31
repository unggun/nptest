<?php

namespace Icube\MisiPintar\Model\Resolver\DataProvider;

use Icube\MisiPintar\Helper\GraphQl as GraphQlHelper;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class MissionList
{

    public function __construct(
        DataProcessorPool $dataProcessorPool,
        GetCustomer $getCustomer,
        GraphQlHelper $helper
    ) {
        $this->dataProcessorPool = $dataProcessorPool;
        $this->getCustomer = $getCustomer;
        $this->helper = $helper;
    }

    public function getMissionList($context)
    {
        $customer = $this->getCustomer->execute($context);
        $remoteData = $this->helper->getMissionList($customer);
        return $this->dataProcessorPool->process($remoteData, 'missions');
    }
}
