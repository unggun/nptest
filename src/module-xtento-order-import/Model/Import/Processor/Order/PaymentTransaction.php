<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-03-26T12:08:02+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Order/PaymentTransaction.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor\Order;

class PaymentTransaction extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
{
    /**
     * @var \Magento\Sales\Model\Order\Payment\TransactionFactory
     */
    protected $paymentTransactionFactory;

    /**
     * PaymentTransaction constructor.
     *
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $paymentTransactionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Sales\Model\Order\Payment\TransactionFactory $paymentTransactionFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->paymentTransactionFactory = $paymentTransactionFactory;
    }

    /**
     * @param $updateData
     *
     * @return bool
     */
    public function validate(&$updateData)
    {
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $updateData
     *
     * @return bool
     */
    public function process(&$order, &$updateData)
    {
        if (!isset($updateData['items']) || !is_array($updateData['items']) || empty($updateData['items'])) {
            return true;
        }

        $isOrderNew = $this->isObjectNew($updateData, 'order');
        if (!$isOrderNew) return true;
        return $this->importPaymentTransactionData($order, $updateData['payment_transactions'], $isOrderNew);
    }

    /**
     * @param $order
     * @param $transactions
     * @param $isOrderNew
     *
     * @return array|bool
     */
    protected function importPaymentTransactionData(&$order, &$transactions, $isOrderNew)
    {

        $warnings = [];
        $paymentTransactions = [];

        if (is_array($transactions)) {
            foreach ($transactions as $transactionData) {
                if (!is_array($transactionData)) {
                    continue;
                }
                if ($isOrderNew) {
                    /* @var $paymentTransaction \Magento\Sales\Model\Order\Payment\TransactionFactory */
                    $paymentTransaction = $this->paymentTransactionFactory->create();

                    foreach ($transactionData as $fieldName => $value) {
                        if (is_array($value)) {
                            continue;
                        }
                        $ignoredFields = array('transaction_id', 'parent_id', 'order_id', 'payment_id');
                        if (array_key_exists($fieldName, $ignoredFields)) {
                            continue;
                        }
                        $fieldValue = (string)$value;
                        $paymentTransaction->setData($fieldName, $fieldValue);
                    }

                    $paymentTransactions[] = $paymentTransaction;
                } else {
                    /*$collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                        ->setOrderFilter($this->getOrder())
                        ->addPaymentIdFilter($this->getId())
                        ->addTxnTypeFilter($txnType);*/
                }
            }
        }

        $order->setData('xtento_transactions', $paymentTransactions);

        if (!empty($warnings)) {
            return [
                'stop' => false,
                'message' => implode(", ", $warnings)
            ];
        } else {
            return true;
        }
    }
}