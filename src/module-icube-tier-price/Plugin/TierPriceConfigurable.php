<?php

namespace Icube\TierPrice\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Format;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Icube\TierPrice\helper\Data as HelperData;
 
class TierPriceConfigurable
{
    public function __construct(
        Format $localeFormat = null,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
        PriceCurrencyInterface $priceCurrency,
        HelperData $helperData
    ) {
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(Format::class);
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->priceCurrency = $priceCurrency;
        $this->helperData = $helperData;
    }

    public function afterGetJsonConfig(\Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject, $result)
    {
        $result = $this->jsonDecoder->decode($result);
        $option = $this->getOptionPrices($subject);
        $result['optionPrices'] = $option;

        return $this->jsonEncoder->encode($result);
    }

    protected function getOptionPrices($subject)
    {
        $prices = [];
        foreach ($subject->getAllowProducts() as $product) {
            $priceInfo = $product->getPriceInfo();

            $prices[$product->getId()] = [
                'baseOldPrice' => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('regular_price')->getAmount()->getBaseAmount()
                    ),
                ],
                'oldPrice' => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('regular_price')->getAmount()->getValue()
                    ),
                ],
                'basePrice' => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount()
                    ),
                ],
                'finalPrice' => [
                    'amount' => $this->localeFormat->getNumber(
                        $priceInfo->getPrice('final_price')->getAmount()->getValue()
                    ),
                ],
                'tierPrices' => $this->getTierPricesByProduct($product),
                'msrpPrice' => [
                    'amount' => $this->localeFormat->getNumber(
                        $this->priceCurrency->convertAndRound($product->getMsrp())
                    ),
                ],
            ];
        }

        return $prices;
    }

    private function getTierPricesByProduct(ProductInterface $product): array
    {
        $tierPrices = [];
        $tierPriceModel = $product->getPriceInfo()->getPrice('tier_price');
        $tierPriceList = $this->helperData->getTierPriceDetailsByProduct($product);
        foreach ($tierPriceList as $tierPrice) {
            $groupCode = ($tierPrice['cust_group'] !== '32000') ? $this->helperData->getCustomerGroupCode($tierPrice['cust_group'])->getCode() : null;
            if ($tierPrice['all_groups'] == 0 && $tierPrice['price_value_type'] == 'fixed') {
                $text = 'For customer group '.$groupCode.' Buy '.$tierPrice['price_qty'].' Discount '.$this->priceCurrency->convertAndFormat($tierPrice['price']->getValue());
            } elseif ($tierPrice['all_groups'] == 0 && $tierPrice['price_value_type'] == 'percent') {
                $text = 'For customer group '.$groupCode.' Buy '.$tierPrice['price_qty'].' Discount '.$tierPrice['price']->getValue().'%';
            } elseif ($tierPrice['price_value_type'] == 'fixed') {
                $text = 'Buy '.$tierPrice['price_qty'].' Discount '.$this->priceCurrency->convertAndFormat($tierPrice['price']->getValue());
            } else {
                $text = 'Buy '.$tierPrice['price_qty'].' Discount '.$tierPrice['price']->getValue().'%';
            }

            $tierPriceData = [
                'qty' => $this->localeFormat->getNumber($tierPrice['price_qty']),
                'price' => $this->localeFormat->getNumber($tierPrice['price']->getValue()),
                'text' => $text,
                'percentage' => $this->localeFormat->getNumber(
                    $tierPriceModel->getSavePercent($tierPrice['price'])
                ),
            ];

            if (isset($tierPrice['excl_tax_price'])) {
                $excludingTax = $tierPrice['excl_tax_price'];
                $tierPriceData['excl_tax_price'] = $this->localeFormat->getNumber($excludingTax->getBaseAmount());
            }
            $tierPrices[] = $tierPriceData;
        }

        return $tierPrices;
    }
}
