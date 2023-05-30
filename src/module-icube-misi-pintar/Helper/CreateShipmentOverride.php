<?php 
namespace Icube\MisiPintar\Helper;

class CreateShipmentOverride extends \Swiftoms\Shipment\Helper\CreateShipment
{
    protected $customerFactory;

    public function __construct(    
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Swift\Webhook\Model\SwiftwebhookFactory $swiftwebhookFactory,
        \Swift\Webhook\Api\PostManagementInterface $postManagement,
        \Magento\Sales\Model\Order $orderFactory,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Framework\App\State $state,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ){
        $this->customerFactory = $customerFactory;
        parent::__construct($scopeConfig,$swiftwebhookFactory,$postManagement,$orderFactory,$convertOrder,$shipmentNotifier,$shipmentFactory,$trackFactory,$state);
    }

    public function createShipment()
    {
        try {
            $this->state->setAreaCode('adminhtml');            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
             
        }

        $swiftwebhooks = $this->swiftwebhookFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('event',['eq' => self::SHIPMENTCREATION_EVENT])
                        ->addFieldToFilter('status',['eq' => self::STATUS_PENDING])
                        ->setPageSize(10);

        foreach($swiftwebhooks as $swiftwebhook):
            $webhookId = $swiftwebhook->getId();
            $data = json_decode($swiftwebhook->getDatajson(),true);
            $orderId = $data['channel_order_increment_id']; 
            $order  = $this->orderFactory->loadByAttribute('increment_id', $orderId);
            if($data['shipments'])
            {
                $jsonShipment = $data['shipments']; 
                foreach($jsonShipment as $shipments){
                    $getInventorySource = json_decode($this->getInventorySource(), true);
                    $locationMap = array_filter($getInventorySource, function($elem) use ($shipments) {
                        $vs_name = $elem['vs_name'] ?? '';
                    $vs_name2 = $shipments['vs_name'] ?? '';
                        return $vs_name == $vs_name2;
                    });

                    if ($locationMap) {
                        foreach ($locationMap as $m) {
                            $channel = $m['channel_source'];
                        }
                        if (isset($shipments['tracking_number'])) {
                            $this->proceedShipment($order,$webhookId,$shipments['items'],$channel,$shipments['tracking_number']);
                        } else {
                            $this->proceedShipment($order,$webhookId,$shipments['items'],$channel,0);
                        }

                        //override - also send data to warpin
                        $this->sendDataToWarpin($data,$order);
                    }
                }
            }
        endforeach;
    }

    protected function sendDataToWarpin($dataOms,$order)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/sendDataMisiPintarToWarpin.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        

        $customer = $this->customerFactory->create()->setWebsiteId(0)->loadByEmail($dataOms['customer_email']);
        $invoice = '';

        foreach($order->getInvoiceCollection() as $invoiceData){
            $invoice = $invoiceData;
        } 
        $dataItem = array();

        foreach ($order->getAllVisibleItems() as $item) {
            $dataItem[] = array(
                "price_unit"=> (int)$item->getPrice(),
                "product_name"=> $item->getName(),
                "product_qty"=> (int)$item->getQtyOrdered(),
                "product_delivered_qty"=> (int)$item->getQtyShipped(),
                "product_invoiced_qty"=> (int)$item->getQtyInvoiced(),
                "product_sku"=> $item->getSku(),
                "product_uom"=> ""
            );
        }

        $wp_code = '';
        if($wpCodeAttribute = $customer->getCustomAttribute('wp_code')) {
            $wp_code = $wpCodeAttribute->getValue();
        }

        if($wp_code == '' || $wp_code == NULL){
            $emailSplit = explode('@', $dataOms['customer_email']);
            $wp_code = $emailSplit[0];
        }

        $data = array(
            "data" => array(
                "external_ref" => $dataOms['channel_order_increment_id'],
                "origin_event_id" => "",                            //no need to fill this 
                "sale_order_number" => $this->getInvoiceIncrement($invoice),  //invoice number
                "sale_order_status" => "invoice.paid",              //hardcode since status needed
                "sale_order_date" => $order->getCreatedAt(),
                "sale_order_total_price" => (int)$order->getGrandTotal(),
                "sale_order_voucher_code" => "",
                "payment_method" => "COD",
                "recipient_code" => $wp_code,
                "sale_order_invoice" => 
                array(
                    array(
                        "invoice_number" => $this->getInvoiceIncrement($invoice),
                        "invoice_date" => $this->getInvoiceGetCreatedAt($invoice),
                        "invoice_item" => $dataItem
                    )
                )
                
            )
        );

        $logger->info('data send '.json_encode($data));

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->getOmsNotifyUrlEndpoint(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
        ),
        ));
        
        $response = curl_exec($curl);

        $logger->info('data send '.json_encode($response));
    }

    private function getInvoiceIncrement($invoice){
        if(is_string($invoice)){
            return '';
        }else{
            return $invoice->getIncrementId();
        }
    }

    private function getInvoiceGetCreatedAt($invoice){
        if(is_string($invoice)){
            return '';
        }else{
            return $invoice->getCreatedAt();
        }
    }

    public function getOmsNotifyUrlEndpoint() 
    {
        return $this->scopeConfig->getValue('misipintar/fulfillment/oms_notify_url_endpoint', $this->_storeScope);
    }

}