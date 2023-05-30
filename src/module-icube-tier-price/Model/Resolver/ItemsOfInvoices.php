<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\TierPrice\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\OrderFactory;

/**
 * @inheritdoc
 */
class ItemsOfInvoices implements ResolverInterface
{

    /**
     * @var OrderFactory
     */
    private $order;

    /**
     * GetInvoices constructor.
     *
     * @param OrderFactory $order
     */
    public function __construct(
        OrderFactory $order
    ) {
        $this->order = $order;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        try {
            $invoiceIds = [];
            $invoiceIdsList = [];
            $orderdetails = $this->order->create()->getCollection()
                        ->addFieldToFilter('increment_id', $args['order_id'])
                        ->addFieldToFilter('customer_id', $context->getUserId())
                        ->getFirstItem();
            if (!empty($orderdetails->getData())) {
                foreach ($orderdetails->getInvoiceCollection() as $invoice) {
                    $invoiceIds[] = $invoice->getIncrementId();
                }
                $invoiceIdsList['items'] = $invoiceIds;

                return $invoiceIdsList;
            } else {
                throw new GraphQlNoSuchEntityException(__('Invoice not found.'));
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}
