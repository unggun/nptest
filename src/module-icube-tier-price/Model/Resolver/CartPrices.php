<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\TierPrice\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;

/**
 * @inheritdoc
 */
class CartPrices extends \Magento\QuoteGraphQl\Model\Resolver\CartPrices
{
    /**
     * @var TotalsCollector
     */
    private $totalsCollector;

    /**
     * @param TotalsCollector $totalsCollector
     */
    public function __construct(
        TotalsCollector $totalsCollector
    ) {
        $this->totalsCollector = $totalsCollector;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Quote $quote */
        $quote = $value['model'];
        $subtotalInclTax = 0;
        foreach ($quote->getItems() as $item) {
            $subtotalInclTax += $item->getRowTotalInclTax();
        }
        /**
         * To calculate a right discount value
         * before calculate totals
         * need to reset Cart Fixed Rules in the quote
         */
        $quote->setCartFixedRules([]);
        $cartTotals = $this->totalsCollector->collectQuoteTotals($quote);
        $currency = $quote->getQuoteCurrencyCode();

        return [
            'grand_total' => ['value' => $quote->getGrandTotal(), 'currency' => $currency],
            'subtotal_including_tax' => ['value' => $subtotalInclTax, 'currency' => $currency],
            'subtotal_excluding_tax' => ['value' => $quote->getSubtotal(), 'currency' => $currency],
            'subtotal_with_discount_excluding_tax' => [
                'value' => $quote->getSubtotalWithDiscount(), 'currency' => $currency
            ],
            'applied_taxes' => $this->getAppliedTaxes($cartTotals, $currency),
            'discount' => $this->getDiscount($cartTotals, $currency),
            'tier_price' => $this->getTierPriceDiscount($quote, $currency),
            'nett' => $this->getNett($cartTotals, $currency),
            'model' => $quote
        ];
    }

    /**
     * Returns taxes applied to the current quote
     *
     * @param Total $total
     * @param string $currency
     * @return array
     */
    private function getAppliedTaxes(Total $total, string $currency): array
    {
        $appliedTaxesData = [];
        $appliedTaxes = $total->getAppliedTaxes();

        if (empty($appliedTaxes)) {
            return $appliedTaxesData;
        }

        foreach ($appliedTaxes as $appliedTax) {
            $appliedTaxesData[] = [
                'label' => $appliedTax['id'],
                'percent' => $appliedTax['percent'],
                'amount' => ['value' => $appliedTax['amount'], 'currency' => $currency]
            ];
        }
        return $appliedTaxesData;
    }

    /**
     * Returns information about an applied discount
     *
     * @param Total $total
     * @param string $currency
     * @return array|null
     */
    private function getDiscount(Total $total, string $currency)
    {
        if ($total->getDiscountAmount() === 0) {
            return null;
        }
        return [
            'label' => explode(', ', $total->getDiscountDescription()),
            'amount' => ['value' => $total->getDiscountAmount(), 'currency' => $currency]
        ];
    }

    /**
     * Returns information about an applied tier price
     *
     * @param Quote $quote
     * @param string $currency
     * @return array|null
     */
    private function getTierPriceDiscount(Quote $quote, string $currency)
    {
        $discount = 0;
        foreach ($quote->getAllItems() as $quoteItem) {
            $tmp =  0;
            if ($quoteItem->getTierPriceData()) {
                foreach (json_decode($quoteItem->getTierPriceData(), true) as $tierPrice) {
                    if (!$tierPrice['apply_to_price']) {
                        if (isset($tierPrice['discount_percentage']) && $tierPrice['discount_percentage'] && ($tierPrice['discount_percentage'] > 0)) {
                            $tmp = ((int) $quoteItem->getPrice() * $tierPrice['discount_percentage']) / 100;
                        } elseif (isset($tierPrice['discount_amount']) && $tierPrice['discount_amount']) {
                            $tmp = $tierPrice['discount_amount'];
                        } else {
                            $tmp = 0;
                        }
                    }
                }
                $discount += $tmp * $quoteItem->getQtyTierPrice();
                $discount = round($discount);
            }
        }

        if (!$discount) {
            return null;
        }

        return [
            'label' => __('Tier Discount'),
            'amount' => ['value' => '-'.$discount, 'currency' => $currency]
        ];
    }

    /**
     * Returns information about nett
     *
     * @param Total $total
     * @param string $currency
     * @return array|null
     */
    private function getNett(Total $total, string $currency)
    {
        $nettTotal = 0;
        $totalAmount = $total->getAllTotalAmounts();
        if (isset($totalAmount['shipping'])) {
            unset($totalAmount['shipping']);
        }
        if (isset($totalAmount['shipping_discount'])) {
            unset($totalAmount['shipping_discount']);
        }
        $totals = array_sum($totalAmount);
        $nettTotal = max(0, $totals - $total->getTaxAmount());

        return [
            'label' => __('Nett'),
            'amount' => ['value' => $nettTotal, 'currency' => $currency]
        ];
    }
}
