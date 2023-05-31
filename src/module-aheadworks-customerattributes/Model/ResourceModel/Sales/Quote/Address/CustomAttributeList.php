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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Quote\Address;

use Aheadworks\CustomerAttributes\Model\Attribute\Provider;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;

/**
 * Class CustomAttributeList
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Quote\Address
 */
class CustomAttributeList implements CustomAttributeListInterface
{
    /**
     * @var Provider
     */
    private $provider;

    /**
     * @var array
     */
    private $attributeList;

    /**
     * @param Provider $provider
     */
    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {
        if ($this->attributeList === null) {
            $this->attributeList = array_flip((array)$this->provider->getOrderAddressAttributeCodes());
        }

        return $this->attributeList;
    }
}
