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
namespace Aheadworks\CustomerAttributes\Block\Checkout;

use Magento\Checkout\Block\Checkout\AttributeMerger as CheckoutAttributeMerger;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class AttributeMerger
 * @package Aheadworks\CustomerAttributes\Block\Checkout
 */
class AttributeMerger extends CheckoutAttributeMerger
{
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param AddressHelper $addressHelper
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param DirectoryHelper $directoryHelper
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        AddressHelper $addressHelper,
        Session $customerSession,
        CustomerRepository $customerRepository,
        DirectoryHelper $directoryHelper,
        TimezoneInterface $localeDate
    ) {
        parent::__construct($addressHelper, $customerSession, $customerRepository, $directoryHelper);
        $this->localeDate = $localeDate;
        $this->formElementMap['date'] = 'Magento_Ui/js/form/element/date';
    }

    /**
     * {@inheritDoc}
     */
    protected function getFieldConfig(
        $attributeCode,
        array $attributeConfig,
        array $additionalConfig,
        $providerName,
        $dataScopePrefix
    ) {
        $config = parent::getFieldConfig(
            $attributeCode,
            $attributeConfig,
            $additionalConfig,
            $providerName,
            $dataScopePrefix
        );

        if ($attributeConfig['formElement'] == 'date') {
            $config['config']['options'] = ['dateFormat' => $this->localeDate->getDateFormat()];
            if (isset($config['value'])) {
                $config['value'] = $config['shiftedValue'] = $this->localeDate->formatDate($config['value']);
            }
        }

        return $config;
    }
}
