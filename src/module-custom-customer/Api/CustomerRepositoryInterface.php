<?php

namespace Icube\CustomCustomer\Api;


interface CustomerRepositoryInterface
{

    /**
     * Create or update a customer.
     * @param string $customerawpcode
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $passwordHash
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\InputException If bad input is provided
     * @throws \Magento\Framework\Exception\State\InputMismatchException If the provided email is already used
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save($customerawpcode,\Magento\Customer\Api\Data\CustomerInterface $customer, $passwordHash = null);

}
