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

use Aheadworks\CustomerAttributes\Model\Attribute\Provider;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;

/**
 * Class Quote
 * @package Aheadworks\CustomerAttributes\Model\Sales\AttributesData\FieldsetProcessor
 */
class Quote implements ProcessorInterface
{
    /**
     * @var Provider
     */
    private $attributesProvider;

    /**
     * @param Provider $attributesProvider
     */
    public function __construct(
        Provider $attributesProvider
    ) {
        $this->attributesProvider = $attributesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process($result, $name)
    {
        $attributeCodes = $this->attributesProvider->getOrderAttributeCodes(false);

        foreach ($attributeCodes as $attributeCode) {
            $result[$attributeCode] = [
                'to_quote' => Attribute::COLUMN_PREFIX . $attributeCode
            ];
        }

        return $result;
    }
}
