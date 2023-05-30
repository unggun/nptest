<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-05-18T13:17:52+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Processor/Order/Payment.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Processor\Order;

class Payment extends \Xtento\OrderImport\Model\Import\Processor\AbstractProcessor
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentData;

    /**
     * @var \Magento\Sales\Model\Order\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * Payment constructor.
     *
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Sales\Model\Order\PaymentFactory $paymentFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->paymentData = $paymentData;
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @param $updateData
     *
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate(&$updateData)
    {
        // Check only if object is new, otherwise do not check (not required for address, not required for delete mode)
        if (!$this->isObjectNew($updateData, 'order') || !$this->isCreateMode($updateData)) {
            return true;
        } else {
            $paymentMethod = 'checkmo';
            if (isset($updateData['order_payment']) && isset($updateData['order_payment']['method'])) {
                $paymentMethod = $updateData['order_payment']['method'];
            }
            // Check is valid payment method
            $paymentMethodInstance = $this->paymentData->getMethodInstance($paymentMethod);
            if (empty($paymentMethodInstance)) {
                return [
                    'stop' => false,
                    'message' => __(
                        'Payment method "%1" supplied in import file is not existing in this store, using checkmo instead.', $paymentMethod
                    )
                ];
            }
            $updateData['order_payment']['method'] = $paymentMethod;
        }

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
        $isOrderNew = $this->isObjectNew($updateData, 'order');

        if (!isset($updateData['order_payment']) || !$updateData['order_payment'] || empty($updateData['order_payment'])) {
            return true;
        }

        $this->importPaymentData($order, $updateData['order_payment'], $isOrderNew);

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $paymentData
     * @param $isOrderNew
     */
    protected function importPaymentData(&$order, &$paymentData, $isOrderNew)
    {
        if ($isOrderNew) {
            /* @var $payment \Magento\Sales\Model\Order\Payment */
            $payment = $this->paymentFactory->create();
        } else {
            /* @var $payment \Magento\Sales\Model\Order\Payment */
            $payment = $order->getPayment();
        }

        foreach ($paymentData as $fieldName => $value) {
            if (is_array($value)) {
                continue;
            }
            $ignoredFields = ['entity_id', 'parent_id', 'quote_payment_id'];
            if (array_key_exists($fieldName, $ignoredFields)) {
                continue;
            }
            if ($fieldName == 'method_instance' && empty($value)) {
                continue;
            }
            $fieldValue = (string)$value;
            if ($fieldName == 'additional_information') {
                try {
                    $fieldValue = json_decode($fieldValue);
                } catch (\Exception $e) {
                    $fieldValue = [];
                }
            }
            $payment->setData($fieldName, $fieldValue);
        }

        if ($isOrderNew) {
            $order->setPayment($payment);
        }
    }
}