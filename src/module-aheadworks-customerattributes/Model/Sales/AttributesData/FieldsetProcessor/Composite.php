<?php
/**
 * Aheadworks Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://aheadworks.com/end-user-license-agreement/
 *
 * @package    CustomerAttributes
 * @version    1.1.1
 * @copyright  Copyright (c) 2021 Aheadworks Inc. (https://aheadworks.com/)
 * @license    https://aheadworks.com/end-user-license-agreement/
 */
namespace Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class Composite
 * @package Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor
 */
class Composite
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $fieldsetMap = [
        'sales_copy_order' => 'order',
        'sales_convert_quote' => 'order',
        'customer_account' => 'quote',
        'sales_copy_order_billing_address' => 'order_address',
        'sales_copy_order_shipping_address' => 'order_address',
        'sales_convert_quote_address' => 'order_address',
        'order_address' => 'order_address'
    ];

    /**
     * @var array
     */
    private $processorClasses = [
        'order' => Order::class,
        'order_address' => OrderAddress::class,
        'quote' => Quote::class
    ];

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $fieldsetMap
     * @param array $processorClasses
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $fieldsetMap = [],
        array $processorClasses = []
    ) {
        $this->objectManager = $objectManager;
        $this->processorClasses = array_merge($this->processorClasses, $processorClasses);
        $this->fieldsetMap = array_merge($this->fieldsetMap, $fieldsetMap);
    }

    /**
     * Add attribute codes to fieldset as fields
     *
     * @param array $result
     * @param string $name
     * @return array
     */
    public function process($result, $name)
    {
        if ($processorClass = $this->getProcessor($result, $name)) {
            $processor = $this->objectManager->create($processorClass);
            if ($processor instanceof ProcessorInterface) {
                $result = $processor->process($result, $name);
            }
        }

        return $result;
    }

    /**
     * Retrieve processor class
     *
     * @param array $result
     * @param string $name
     * @return string|null
     */
    private function getProcessor($result, $name)
    {
        $className = null;

        if (is_array($result) && array_key_exists($name, $this->fieldsetMap)) {
            $type = $this->fieldsetMap[$name];
            $className = array_key_exists($type, $this->processorClasses)
                ? $this->processorClasses[$type]
                : $className;
        }

        return $className;
    }
}
