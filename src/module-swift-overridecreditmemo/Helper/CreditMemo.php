<?php
namespace Swift\CreditmemoOverride\Helper;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\OfflinePayments\Model\Cashondelivery;

class CreditMemo extends \Swift\CreditMemo\Helper\CreditMemo
{
    public function consumeCreditMemo()
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            echo "error area code";
        }

        $swiftwebhooksData = $this->getWebhooksData();

        foreach ($swiftwebhooksData as $swiftwebhook) {
            $webhookId = $swiftwebhook->getId();
            $data = json_decode($swiftwebhook->getDatajson(), true);
            $orderId = $data['channel_order_increment_id']; 
            $order  = $this->orderFactory->create()->loadByAttribute('increment_id', $orderId);
            if($data['creditmemo']){
                try {
                    $this->processCreditMemo($order, $webhookId, $data);
                } catch (\Throwable $th) {
                    echo $th->getMessage() . PHP_EOL;
                }
            }
        }
    }

    protected function processCreditMemo(OrderInterface $order, $webhookId, $data)
    {
        $swiftwebhook = $this->swiftwebhookFactory->create();
        $swiftwebhook->load($webhookId);
        $swiftwebhook->setStatus(self::STATUS_PROCESSING);
        $swiftwebhook->save();

        if ($order->getEntityId()) {
            $invoices = $order->getInvoiceCollection();
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                $swiftwebhook->setStatus(self::STATUS_SUCCESS);
                $swiftwebhook->save();
                $this->cancelOrder($order);
                throw new \Exception(__('Order with payment COD is canceled. Magento_ID: %1', $order->getIncrementId()));
            }

            if (count($invoices) == 0 && $paymentMethod != Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                $swiftwebhook->setStatus(self::STATUS_FAIL);
                $swiftwebhook->save();
                $this->cancelOrder($order);
                throw new \Exception(__('No Invoices found for Refund, order canceled. Magento_ID: %1', $order->getIncrementId()));
            }

            $creditMemoService = $this->creditMemoServiceFactory->create();

            foreach ($invoices as $invoice) {
                $invoice = $this->invoice->loadByIncrementId($invoice->getIncrementId());
                $qtys = [];
                foreach ($data['creditmemo']['items'] as $item) {
                    foreach ($invoice->getItems() as $invItem) {
                        if ($item['sku'] == $invItem->getSku()) {
                            $qtys[$invItem->getOrderItemId()] = $item['qty']; 
                        }
                    }
                }
                $data['creditmemo']['qtys'] = $qtys;
                $creditMemo = $this->creditMemoFactory->createByOrder($order, $data['creditmemo']);
                $creditMemo->setCustomerNote(__('Your Order %1 has been Refunded back in your account', $order->getIncrementId()));
                $creditMemo->setCustomerNoteNotify(false);
                $order->addCommentToStatusHistory(__('Order has been refunded successfully'), false, true);
                $creditMemo->addComment(__('Order has been refunded'), false, true);

                $creditMemoService->refund($creditMemo, false);

                $order = $this->orderFactory->create()->loadByAttribute('increment_id', $data['channel_order_increment_id']);
                $orderState = $this->orderStateResolver->getStateForOrder($order, []);
                $order->setState($orderState);
                $statuses = $this->orderConfig->getStateStatuses($orderState, false);
                $status = in_array($order->getStatus(), $statuses, true)
                    ? $order->getStatus()
                    : $this->orderConfig->getStateDefaultStatus($orderState);
                $order->setStatus($status);
                $order->save();
            }

            $swiftwebhook->setStatus(self::STATUS_SUCCESS); 
            $swiftwebhook->save();
        }
    }

    protected function cancelOrder(OrderInterface $order)
    {
        try {
            $orderManagement = $this->objectManager->create('\Magento\Sales\Api\OrderManagementInterface');
            $orderManagement->cancel($order->getEntityId());
        } catch (\Throwable $th) {
            $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/swiftoms.log');
            $logger = new \Laminas\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($th->getMessage());
        }
    }
}
