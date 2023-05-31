<?php
namespace Icube\CustomCustomer\Model;

use Icube\CustomCustomer\Api\Data\CustomerExtensionInterface;

class CustomerExtension implements CustomerExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBusinessType()
    {
        return $this->getData(self::BUSINESS_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setBusinessType($value)
    {
        return $this->setData(self::BUSINESS_TYPE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getOtherBusinessType()
    {
        return $this->getData(self::OTHER_BUSINESS_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setOtherBusinessType($value)
    {
        return $this->setData(self::OTHER_BUSINESS_TYPE, $value);
    }
}
