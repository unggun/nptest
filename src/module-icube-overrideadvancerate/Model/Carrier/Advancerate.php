<?php

namespace Icube\OverrideAdvancerate\Model\Carrier;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\RateRequest;

class Advancerate extends \Ced\Advancerate\Model\Carrier\Advancerate
{
    public function getdefaultRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $is_multiseller = $this->_scopeConfig->getValue(
            'swiftoms_multiseller/configurations/enable_oms_multiseller',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );

        if($is_multiseller){
            $rates = array();
            $seller_id = $request->getData("all_items")[0]->getAddress()->getData("seller_id");
            $customer_id = $request->getData("all_items")[0]->getAddress()->getData("customer_id");

            $con = $this->_objectManager->create('Magento\Framework\App\ResourceConnection')->getConnection();

            $cust = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($customer_id);

            $select = $con->select()->from('advance_rate')->where('vendor_id = :vendor_id')->where('customer_group = :customer_group');
            $bind[':vendor_id'] = $seller_id;
            $bind[':customer_group'] = $cust->getGroupId();
            $result = $con->fetchAll($select,$bind);

            foreach($result as $value){
                $rates[] = array(
                                 'method' => $value['shipping_method'],
                                 'label' => $value['shipping_label'],
                                 'price' => $value['price'],
                                 'etd'   => $value['etd']
                             );
            }
            return $rates;
        }else{
            return $this->_tablerateFactory->create()->getRates($request);
        }
        
    }
}