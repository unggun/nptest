<?php

namespace Icube\TierPrice\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order\InvoiceFactory;

class StatusInvoiceByOrder implements ResolverInterface
{
    /**
     * @var InvoiceFactory
     */
    private $invoice;

    public function __construct(
        InvoiceFactory $invoice
    ) {
        $this->invoice = $invoice;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        if (!empty($value)) {
            $status = null;
            foreach ($value as $idx =>$item) {
                $invoiceData =  $this->invoice->create()->loadByIncrementId($item);
                $invoice[$idx]['invoice_id'] = $invoiceData->getIncrementId();
                switch ($invoiceData->getState()) {
                    case '1':
                        $status = "Pending";
                        break;
                    case '2':
                        $status = "Paid";
                        break;
                    default:
                        $status = "Unknown";
                        break;
                }
                $invoice[$idx]['status_invoice'] = $status;
            }

            return $invoice;
        }

        return null;
    }
}
