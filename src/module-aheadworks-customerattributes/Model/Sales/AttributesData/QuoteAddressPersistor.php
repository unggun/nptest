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
namespace Aheadworks\CustomerAttributes\Model\Sales\AttributesData;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Aheadworks\CustomerAttributes\Model\Sales\Quote\Address;
use Aheadworks\CustomerAttributes\Model\Sales\Quote\AddressFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Quote\Address as AddressResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class QuoteAddressPersistor
 * @package Aheadworks\CustomerAttributes\Model\Sales\AttributesData
 */
class QuoteAddressPersistor
{
    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var AddressResource
     */
    private $addressResource;

    /**
     * @param AddressFactory $addressFactory
     * @param AddressResource $addressResource
     */
    public function __construct(
        AddressFactory $addressFactory,
        AddressResource $addressResource
    ) {
        $this->addressFactory = $addressFactory;
        $this->addressResource = $addressResource;
    }

    /**
     * Save
     *
     * @param AbstractModel $address
     * @throws AlreadyExistsException
     */
    public function save(AbstractModel $address)
    {
        /** @var Address $addressAttributeModel */
        $addressAttributeModel = $this->addressFactory->create();
        $data = $address->getData();
        $data[Attribute::ADDRESS_ID] = $address->getId();
        $addressAttributeModel->addData($data);

        $this->addressResource->save($addressAttributeModel);
    }

    /**
     * Load
     *
     * @param AbstractModel $address
     */
    public function load(AbstractModel $address)
    {
        /** @var Address $addressAttributeModel */
        $addressAttributeModel = $this->addressFactory->create();
        $this->addressResource->load($addressAttributeModel, $address->getId());
        $address->addData($addressAttributeModel->getData());
    }
}
