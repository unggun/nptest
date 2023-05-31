<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Icube\TierPrice\Block\Sales\Order;

/**
 * Customer balance block for order
 *
 * @api
 * @since 100.0.2
 */
class TierPrice extends \Magento\Framework\View\Element\Template
{
    private $discount;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Framework\View\Element\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Initialize customer balance order total
     *
     * @return $this
     */
    public function initTotals()
    {
        if (!$this->getDiscount()) {
            return $this;
        }

        $total = new \Magento\Framework\DataObject(
            [
                'code' => $this->getNameInLayout(),
                'block_name' => $this->getNameInLayout(),
                'area' => $this->getArea(),
            ]
        );
        $after = $this->getAfterTotal();
        if (!$after) {
            $after = 'subtotal';
        }
        $this->getParentBlock()->addTotal($total, $after);
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return string
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    public function getDiscount() {
        if (!$this->discount) {
            $discount = 0;
            foreach ($this->getOrder()->getAllItems() as $orderItem) {
                $tmp =  0;
                if ($orderItem->getTierPriceData()) {
                    foreach (json_decode($orderItem->getTierPriceData(), true) as $tierPrice) {
                        if ($tierPrice['discount_percentage'] && ($tierPrice['discount_percentage'] > 0)) {
                            $tmp = ((int) $orderItem->getPrice() * $tierPrice['discount_percentage']) / 100;
                        } elseif ($tierPrice['discount_amount']) {
                            $tmp = $tierPrice['discount_amount'];
                        } else {
                            $tmp = 0;
                        }
                    }
                    $discount += $tmp * $orderItem->getQtyTierPrice();
                    $discount = round($discount);
                }
            }

            $this->discount = $discount;
        }

        return $this->discount;
    }
}
