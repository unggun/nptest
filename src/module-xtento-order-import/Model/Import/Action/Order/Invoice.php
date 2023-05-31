<?php

/**
 * Product:       Xtento_OrderImport
 * ID:            oqsi2y9WE6C6UMS277bs1A30bLpPe+n3JPzkpe97fvg=
 * Last Modified: 2020-08-19T15:02:35+00:00
 * File:          app/code/Xtento/OrderImport/Model/Import/Action/Order/Invoice.php
 * Copyright:     Copyright (c) XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\OrderImport\Model\Import\Action\Order;

use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Xtento\OrderImport\Model\Import\Action\AbstractAction;
use Xtento\OrderImport\Model\Processor\Mapping\Action\Configuration;

class Invoice extends AbstractAction
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var TransactionFactory
     */
    protected $dbTransactionFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Invoice constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Configuration $actionConfiguration
     * @param ProductFactory $modelProductFactory
     * @param Order\Email\Sender\InvoiceSender $invoiceSender
     * @param TransactionFactory $dbTransactionFactory
     * @param ObjectManagerInterface $objectManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Configuration $actionConfiguration,
        ProductFactory $modelProductFactory,
        Order\Email\Sender\InvoiceSender $invoiceSender,
        TransactionFactory $dbTransactionFactory,
        ObjectManagerInterface $objectManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productFactory = $modelProductFactory;
        $this->invoiceSender = $invoiceSender;
        $this->dbTransactionFactory = $dbTransactionFactory;
        $this->objectManager = $objectManager;
        parent::__construct($context, $registry, $actionConfiguration, $resource, $resourceCollection, $data);
    }

    public function invoice()
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $updateData = $this->getUpdateData();

        // Check if order is holded and unhold if should be shipped
        if ($order->canUnhold() && $this->getActionSettingByFieldBoolean('invoice_create', 'enabled')) {
            $order->unhold()->save();
            $this->addDebugMessage(
                __("Order '%1': Order was unholded so it can be invoiced.", $order->getIncrementId())
            );
        }

        /*if (!$order->canInvoice() && $this->getActionSettingByFieldBoolean('invoice_send_email', 'enabled')) {
            // Re-send invoice email
            $invoices = $order->getInvoiceCollection();
            $lastInvoice = $invoices->getFirstItem();
            if ($lastInvoice->getId()) {
                $lastInvoice->setCustomerNoteNotify(true);
                $this->invoiceSender->send($lastInvoice);
                $lastInvoice->save();
            }
        }*/

        // Create Invoice
        if ($this->getActionSettingByFieldBoolean('invoice_create', 'enabled')) {
            if ($order->canInvoice()) {
                /** @var $invoice \Magento\Sales\Model\Order\Invoice */
                $invoice = $order->prepareInvoice();
                if ($invoice) {
                    if ($this->getActionSettingByFieldBoolean(
                            'invoice_capture_payment',
                            'enabled'
                        ) && $invoice->canCapture()
                    ) {
                        // Capture order online
                        $invoice->setRequestedCaptureCase(OrderInvoice::CAPTURE_ONLINE);
                    } else {
                        if ($this->getActionSettingByFieldBoolean('invoice_mark_paid', 'enabled')) {
                            // Set invoice status to Paid
                            $invoice->setRequestedCaptureCase(OrderInvoice::CAPTURE_OFFLINE);
                        }
                    }

                    try {
                        $invoice->register();
                    } catch (\Exception $e) {
                        throw new LocalizedException(__($e->getMessage()));
                    }
                    if ($this->getActionSettingByFieldBoolean('invoice_send_email', 'enabled')) {
                        $invoice->setCustomerNoteNotify(true);
                    } else {
                        // Mark as sent so async email sending does not re-send
                        $invoice->setEmailSent(true);
                    }
                    $invoice->getOrder()->setIsInProcess(true);

                    $transactionSave = $this->dbTransactionFactory->create()
                        ->addObject($invoice)->addObject($invoice->getOrder());
                    $transactionSave->save();

                    $this->setHasUpdatedObject(true);

                    if ($this->getActionSettingByFieldBoolean('invoice_send_email', 'enabled')) {
                        $this->invoiceSender->send($invoice);
                        $this->addDebugMessage(
                            __(
                                "Order '%1' has been invoiced and the customer has been notified.",
                                $order->getIncrementId()
                            )
                        );
                    } else {
                        $this->addDebugMessage(
                            __(
                                "Order '%1' has been invoiced and the customer has NOT been notified.",
                                $order->getIncrementId()
                            )
                        );
                    }

                    unset($invoice);
                }
            } else {
                $this->addDebugMessage(
                    __(
                        "Order '%1' has NOT been invoiced. Order already invoiced or order status not allowing invoicing.",
                        $order->getIncrementId()
                    )
                );
            }
        }

        return true;
    }
}