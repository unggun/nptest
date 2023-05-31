<?php
namespace Icube\CartOrderLog\Model\Checkout;

use Swiftoms\Multiseller\Model\Checkout\Multiseller as OriginalClass;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Directory\Model\AllowedCountries;
use Aheadworks\Giftcard\Model\Order\MultiShipping\Applier as MultiShippingApplier;
use Aheadworks\Giftcard\Model\Source\History\EntityType as SourceHistoryEntityType;
use Aheadworks\Giftcard\Model\Source\History\Comment\Action as SourceHistoryCommentAction;
use Magento\Framework\App\ResourceConnection;

class Multiseller extends OriginalClass
{
	/**
     * Initialize dependencies.
     *
     * @var \Magento\Multishipping\Helper\Data
     */
    protected $helper;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager = null;

    /**
     * @var \Magento\Quote\Api\Data\CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor
     */
    private $shippingAssignmentProcessor;

    protected $giftcardRepository;

    protected $historyEntityFactory;

    protected $historyActionFactory;

    /**
     * @param \Magento\Multishipping\Helper\Data $helper
     * @param AddressRepositoryInterface $addressRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Quote\Api\Data\CartExtensionFactory|null $cartExtensionFactory
     */
    public function __construct(
        \Magento\Multishipping\Helper\Data $helper,
        AddressRepositoryInterface $addressRepository,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Api\Data\CartExtensionFactory $cartExtensionFactory = null,
        \Magento\Framework\Session\Generic $session,
        OrderSender $orderSender,
        LoggerInterface $logger = null,
        \Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderFactory $placeOrderFactory = null,
        \Magento\Quote\Model\Quote\Item\ToOrderItem $quoteItemToOrderItem,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Quote\Model\Quote\Payment\ToOrderPayment $quotePaymentToOrderPayment,
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $quoteAddressToOrderAddress,
        \Magento\Quote\Model\Quote\Address\ToOrder $quoteAddressToOrder,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper = null,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Aheadworks\RewardPoints\Controller\Adminhtml\Transactions\PostDataProcessor $dataProcessor,
        \Aheadworks\RewardPoints\Api\CustomerRewardPointsManagementInterface $customerRewardPointsService,
        \Aheadworks\StoreCredit\Api\CustomerStoreCreditManagementInterface $customerStoreCreditService,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Amasty\Extrafee\Model\ResourceModel\ExtrafeeOrder\CollectionFactory $feeOrderCollectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Amasty\Extrafee\Model\ResourceModel\ExtrafeeInvoice\CollectionFactory $feeInvoiceCollectionFactory,
        AllowedCountries $allowedCountryReader = null,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        MultiShippingApplier $multiShippingApplier,
        \Aheadworks\Giftcard\Api\GiftcardRepositoryInterface $giftcardRepository,
        \Aheadworks\Giftcard\Api\Data\Giftcard\History\EntityInterfaceFactory $historyEntityFactory,
        \Aheadworks\Giftcard\Api\Data\Giftcard\HistoryActionInterfaceFactory $historyActionFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Payment\Collection $quotePayment,
        ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->addressRepository = $addressRepository;
        $this->_addressFactory = $addressFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productFactory = $productFactory;
        $this->cartExtensionFactory = $cartExtensionFactory ?: ObjectManager::getInstance()
            ->get(\Magento\Quote\Api\Data\CartExtensionFactory::class);
        $this->_session = $session;
        $this->orderSender = $orderSender;
        $this->logger = $logger ?: ObjectManager::getInstance()
            ->get(LoggerInterface::class);
        $this->placeOrderFactory = $placeOrderFactory ?: ObjectManager::getInstance()
            ->get(\Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderFactory::class);
        $this->quoteItemToOrderItem = $quoteItemToOrderItem;
        $this->priceCurrency = $priceCurrency;
        $this->quotePaymentToOrderPayment = $quotePaymentToOrderPayment;
        $this->quoteAddressToOrderAddress = $quoteAddressToOrderAddress;
        $this->quoteAddressToOrder = $quoteAddressToOrder;
        $this->dataObjectHelper = $dataObjectHelper ?: ObjectManager::getInstance()
            ->get(\Magento\Framework\Api\DataObjectHelper::class);
        $this->_orderFactory = $orderFactory;
        $this->customerRepository = $customerRepositoryInterface;
        $this->dataPersistor = $dataPersistor;
        $this->dataProcessor = $dataProcessor;
        $this->customerRewardPointsService = $customerRewardPointsService;
        $this->customerStoreCreditService = $customerStoreCreditService;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->feeOrderCollectionFactory = $feeOrderCollectionFactory;
        $this->moduleManager = $moduleManager;
        $this->feeInvoiceCollectionFactory = $feeInvoiceCollectionFactory;
        $this->allowedCountryReader = $allowedCountryReader ?: ObjectManager::getInstance()
            ->get(AllowedCountries::class);
        $this->_regionFactory = $regionFactory;
        $this->multiShippingApplier = $multiShippingApplier;
        $this->giftcardRepository = $giftcardRepository;
        $this->historyEntityFactory = $historyEntityFactory;
        $this->historyActionFactory = $historyActionFactory;
        $this->quotePayment = $quotePayment;
        $this->resourceConnection = $resourceConnection;
    }

    public function setShippingItemsInformation($info, $shippingAddressesInput, $customer_id, $cart)
    {
        if (is_array($info)) {
            $allQty = 0;
            $itemsInfo = [];
            foreach ($info as $itemData) {
                foreach ($itemData as $quoteItemId => $data) {
                    $allQty += $data['qty'];
                    $itemsInfo[$quoteItemId] = $data;
                }
            }

            foreach ($info as $itemData) {
                foreach ($itemData as $quoteItemId => $data) {
                    $data['quote_item_id'] = $quoteItemId;
                    $group[$data['seller_id']][] = $data;
                }
            }

            $maxQty = $this->helper->getMaximumQty();
            if ($allQty > $maxQty) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Maximum qty allowed for Shipping to multiple addresses is %1', $maxQty)
                );
            }
            $quote = $cart;

            if (isset($shippingAddressesInput['address']['latitude']) || isset($shippingAddressesInput['address']['longitude'])) 
            {
                $quote->setDestLongitude($shippingAddressesInput['address']['longitude']);
                $quote->setDestLatitude($shippingAddressesInput['address']['latitude']);
            }

            if (isset($shippingAddressesInput['customer_address_id'])) 
            {
                $custAddress = $this->addressRepository->getById($shippingAddressesInput['customer_address_id']);
                $quote->setDestLongitude($custAddress->getCustomAttribute('longitude')->getValue());
                $quote->setDestLatitude($custAddress->getCustomAttribute('latitude')->getValue());
            }

            $addresses = $quote->getAllShippingAddresses();
            foreach ($addresses as $address) {
                $quote->removeAddress($address->getId());
            }

            $vendor = null;
            foreach ($group as $value) {
                foreach ($value as $data_seller) {
                    if ($vendor == null || $vendor != $data_seller['seller_id']) {
                        $is_address = 'created';
                        $this->_addShippingItem($data_seller['quote_item_id'], $data_seller, $is_address, $shippingAddressesInput, $customer_id, $cart);
                        $vendor = $data_seller['seller_id'];
                    } else {
                        $is_address = 'not_created';
                        $this->_addShippingItem($data_seller['quote_item_id'], $data_seller, $is_address, $shippingAddressesInput, $customer_id, $cart);
                        $vendor = $data_seller['seller_id'];
                    }
                }
            }

            /**
             * Delete all not virtual quote items which are not added to shipping address
             * MultishippingQty should be defined for each quote item when it processed with _addShippingItem
             */
            foreach ($quote->getAllItems() as $_item) {
                if (
                    !$_item->getProduct()->getIsVirtual() && !$_item->getParentItem() && !$_item->getMultishippingQty()
                ) {
                    $quote->removeItem($_item->getId());
                }
            }

            $billingAddress = $quote->getBillingAddress();
            if ($billingAddress) {
                $quote->removeAddress($billingAddress->getId());
            }
            $customerDefaultBillingId = $this->getCustomerDefaultBillingAddress($customer_id);
            if ($customerDefaultBillingId) {
                $quote->getBillingAddress()->importCustomerAddressData(
                    $this->addressRepository->getById($customerDefaultBillingId)
                );
            }

            foreach ($quote->getAllItems() as $_item) {
                if (!$_item->getProduct()->getIsVirtual()) {
                    continue;
                }

                if (isset($itemsInfo[$_item->getId()]['qty'])) {
                    $qty = (int)$itemsInfo[$_item->getId()]['qty'];
                    if ($qty) {
                        $_item->setQty($qty);
                        $quote->getBillingAddress()->addItem($_item);
                    } else {
                        $_item->setQty(0);
                        $quote->removeItem($_item->getId());
                    }
                }
            }
            
            $this->save($cart);
        }
        
        return $this;
    }

    /**
     * Add quote item to specific shipping address based on customer address id
     *
     * @param int $quoteItemId
     * @param array $data array('qty'=>$qty, 'address'=>$customerAddressId)
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _addShippingItem($quoteItemId, $data, $is_address, $shippingAddressesInput, $customer_id, $cart)
    {
        $qty = isset($data['qty']) ? (int)$data['qty'] : 1;
        $addressId = isset($data['address']) ? (int)$data['address'] : false;
        $quoteItem = $cart->getItemById($quoteItemId);

		$this->loggers("_addShippingItem | [customer ID  ".$customer_id."] - [cart ".$cart->getId()."] | quote Item Id =" . $quoteItemId ." | qty = ". $qty." | address ID =" . $addressId);

        if ($addressId && $quoteItem) {
            if (!$this->isAddressIdApplicable($addressId, $customer_id)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please check shipping address information.'));
            }

            /**
             * Skip item processing if qty 0
             */
            if ($qty === 0) {
                return $this;
            }
            $quoteItem->setMultishippingQty((int)$quoteItem->getMultishippingQty() + $qty);
            $quoteItem->setQty($quoteItem->getMultishippingQty());
            try {
                $address = $this->addressRepository->getById($addressId);
            } catch (\Exception $e) {
            }
            if (isset($address)) {
                if ($is_address == 'created') {
                    $quoteAddress = $this->_addressFactory->create()->importCustomerAddressData($address);
                    $cart->addShippingAddress($quoteAddress);
                    $quoteAddress->setCustomerAddressId($addressId);
                    $product = $this->productFactory->create()->load($quoteItem->getProductId());
                    $quoteAddress->setData('seller_id', $product->getData('seller_id'));
                    $quoteAddressItem = $quoteAddress->getItemByQuoteItemId($quoteItemId);
                    if ($quoteAddressItem) {
                        $quoteAddressItem->setQty((int)($quoteAddressItem->getQty() + $qty));
                    } else {
                        $quoteAddress->addItem($quoteItem, $qty);
                    }
                    /**
                     * Require shipping rate recollect
                     */
                    $quoteAddress->setCollectShippingRates((bool)$this->getCollectRatesFlag());
                } else if ($is_address == 'not_created') {
                    $quoteAddress = $cart->getShippingAddressByCustomerAddressId($address->getId());
                    $quoteAddress->setCustomerAddressId($addressId);
                    $quoteAddressItem = $quoteAddress->getItemByQuoteItemId($quoteItemId);
                    if ($quoteAddressItem) {
                        $quoteAddressItem->setQty((int)($quoteAddressItem->getQty() + $qty));
                    } else {
                        $quoteAddress->addItem($quoteItem, $qty);
                    }
                    /**
                     * Require shipping rate recollect
                     */
                    $quoteAddress->setCollectShippingRates((bool)$this->getCollectRatesFlag());
                }
            }
        } else if (isset($shippingAddressesInput) && $quoteItem) {
            /**
             * Skip item processing if qty 0
             */
            if ($qty === 0) {
                return $this;
            }
            $quoteItem->setMultishippingQty((int)$quoteItem->getMultishippingQty() + $qty);
            $quoteItem->setQty($quoteItem->getMultishippingQty());

            $regionId = $shippingAddressesInput['address']['region_id'] ?? $this->_regionFactory->create()->loadByCode($shippingAddressesInput['address']['region'], $shippingAddressesInput['address']['country_code'])->getId();

            if ($is_address == 'created') {
                $quoteAddress = $this->_addressFactory->create();
                $quoteAddress->setFirstname($shippingAddressesInput['address']['firstname']);
                $quoteAddress->setLastname($shippingAddressesInput['address']['lastname']);
                $quoteAddress->setCompany((isset($shippingAddressesInput['address']['company'])) ? $shippingAddressesInput['address']['company'] : '');
                $quoteAddress->setStreet($shippingAddressesInput['address']['street']);
                $quoteAddress->setCity($shippingAddressesInput['address']['city']);
                $quoteAddress->setRegion($shippingAddressesInput['address']['region']);
                $quoteAddress->setRegionId($regionId);
                $quoteAddress->setPostcode($shippingAddressesInput['address']['postcode']);
                $quoteAddress->setCountryId($shippingAddressesInput['address']['country_code']);
                $quoteAddress->setTelephone($shippingAddressesInput['address']['telephone']);
                $quoteAddress->setSaveInAddressBook($shippingAddressesInput['address']['save_in_address_book']);

                $cart->addShippingAddress($quoteAddress);
                $product = $this->productFactory->create()->load($quoteItem->getProductId());
                $quoteAddress->setData('seller_id', $product->getData('seller_id'));
                $quoteAddressItem = $quoteAddress->getItemByQuoteItemId($quoteItemId);
                if ($quoteAddressItem) {
                    $quoteAddressItem->setQty((int)($quoteAddressItem->getQty() + $qty));
                } else {
                    $quoteAddress->addItem($quoteItem, $qty);
                }
                /**
                 * Require shipping rate recollect
                 */
                $quoteAddress->setCollectShippingRates((bool)$this->getCollectRatesFlag());
            } else if ($is_address == 'not_created') {
                $quoteAddress = $this->_addressFactory->create();
                $quoteAddress->setFirstname($shippingAddressesInput['address']['firstname']);
                $quoteAddress->setLastname($shippingAddressesInput['address']['lastname']);
                $quoteAddress->setCompany((isset($shippingAddressesInput['address']['company'])) ? $shippingAddressesInput['address']['company'] : '');
                $quoteAddress->setStreet($shippingAddressesInput['address']['street']);
                $quoteAddress->setCity($shippingAddressesInput['address']['city']);
                $quoteAddress->setRegion($shippingAddressesInput['address']['region']);
                $quoteAddress->setRegionId($regionId);
                $quoteAddress->setPostcode($shippingAddressesInput['address']['postcode']);
                $quoteAddress->setCountryId($shippingAddressesInput['address']['country_code']);
                $quoteAddress->setTelephone($shippingAddressesInput['address']['telephone']);
                $quoteAddress->setSaveInAddressBook($shippingAddressesInput['address']['save_in_address_book']);

                $quoteAddressItem = $quoteAddress->getItemByQuoteItemId($quoteItemId);
                if ($quoteAddressItem) {
                    $quoteAddressItem->setQty((int)($quoteAddressItem->getQty() + $qty));
                } else {
                    $product = $this->productFactory->create()->load($quoteItem->getProductId());
                    
                    foreach ($cart->getAllShippingAddresses() as $address) {
                        if ($product->getData('seller_id') == $address->getSellerId()) {
                            $address->addItem($quoteItem);
                        }
                    }
                }
                /**
                 * Require shipping rate recollect
                 */
                $quoteAddress->setCollectShippingRates((bool)$this->getCollectRatesFlag());
            }
        }
        return $this;
    }

    /**
     * Retrieve customer default billing address
     *
     * @return int|null
     */
    public function getCustomerDefaultBillingAddress($customer_id)
    {
        try {
            $this->getCustomer($customer_id);
        } catch (\Exception $e) {
            return false;
        }

        $defaultAddressId = $this->getCustomer($customer_id)->getDefaultBilling();
        return $this->getDefaultAddressByDataKey('customer_default_billing_address', $defaultAddressId, $customer_id);
    }

    /**
     * Collect quote totals and save quote object
     *
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    public function save($cart)
    {
		$this->loggers("createOrders | cart =" . $cart->getId());
        $cart->setTotalsCollectedFlag(false)->collectTotals();
        $addresses = $cart->getAllShippingAddresses();
        /** @var  \Magento\Quote\Model\Quote\Address $address */
        foreach ($addresses as $address) {
			$this->loggers("createOrders | [cart - " .$cart->getId()."] | address =" . $address->getQuoteId());
            $address->setCollectShippingRates(true);
            $address->setShippingAmount($address->getShippingInclTax() - $address->getShippingTaxAmount());
            $address->setBaseShippingAmount($address->getBaseShippingInclTax() - $address->getBaseShippingTaxAmount());
        }
        $this->quoteRepository->save($cart);
        return $this;
    }

    /**
     * Check if specified address ID belongs to customer.
     *
     * @param mixed $addressId
     * @return bool
     */
    protected function isAddressIdApplicable($addressId, $customer_id)
    {
        $applicableAddressIds = array_map(
            function ($address) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                return $address->getId();
            },
            $this->getCustomer($customer_id)->getAddresses()
        );

        return in_array($addressId, $applicableAddressIds);
    }

    /**
     * Retrieve checkout session model
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckoutSession()
    {
        $checkout = $this->getData('checkout_session');
        if ($checkout === null) {
            $checkout = $this->_checkoutSession;
            $this->setData('checkout_session', $checkout);
        }
        return $checkout;
    }

    /**
     * Retrieve customer object
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer($customer_id)
    {
        return $this->customerRepository->getById($customer_id);
    }

    /**
     * Retrieve customer default address by data key
     *
     * @param string $key
     * @param string|null $defaultAddressIdFromCustomer
     * @return int|null
     */
    private function getDefaultAddressByDataKey($key, $defaultAddressIdFromCustomer, $customer_id)
    {
        $addressId = $this->getData($key);
        if ($addressId === null) {
            $addressId = $defaultAddressIdFromCustomer;
            if (!$addressId) {
                /** Default address is not available, try to find any customer address */
                $filter = $this->filterBuilder->setField('parent_id')
                    ->setValue($this->getCustomer($customer_id)->getId())
                    ->setConditionType('eq')
                    ->create();
                $addresses = (array)($this->addressRepository->getList(
                    $this->searchCriteriaBuilder->addFilters([$filter])->create()
                )->getItems());
                if ($addresses) {
                    $address = reset($addresses);
                    $addressId = $address->getId();
                }
            }
            $this->setData($key, $addressId);
        }

        return $addressId;
    }

    /**
     * Assign shipping methods to addresses
     *
     * @param  array $methods
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setShippingMethods($methods, $cart)
    {
        $quote = $cart;
        $addresses = $quote->getAllShippingAddresses();
        /** @var  \Magento\Quote\Model\Quote\Address $address */
        foreach ($addresses as $address) {
            $addressId = $address->getId();
            if (isset($methods[$addressId])) {
                $address->setShippingMethod($methods[$addressId]);
                $address->setCollectShippingRates(true);
            } elseif (!$address->getShippingMethod()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Set shipping methods for all addresses. Verify the shipping methods and try again.')
                );
            }
        }


        $this->prepareShippingAssignment($quote);
        $this->save($cart);
        return $this;
    }

    /**
     * Prepare shipping assignment.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote
     */
    private function prepareShippingAssignment($quote)
    {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }
        /** @var \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = $this->getShippingAssignmentProcessor()->create($quote);
        $shipping = $shippingAssignment->getShipping();

        $shipping->setMethod(null);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);
        return $quote->setExtensionAttributes($cartExtension);
    }

    /**
     * Get shipping assignment processor.
     *
     * @return \Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor
     */
    private function getShippingAssignmentProcessor()
    {
        if (!$this->shippingAssignmentProcessor) {
            $this->shippingAssignmentProcessor = ObjectManager::getInstance()
                ->get(\Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor::class);
        }
        return $this->shippingAssignmentProcessor;
    }

    /**
     * Set payment method info to quote payment
     *
     * @param array $payment
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPaymentMethod($payment, $cart)
    {
        if (!isset($payment['method'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("A payment method isn't defined. Verify and try again.")
            );
        }
        $quote = $cart;

        if($payment['method'] == 'free'){
            $quote->setPaymentMethod("free");
            $payment_quotes = $this->quotePayment->addFieldToFilter('quote_id', $quote->getId());
            foreach ($payment_quotes as $payment_quote) {
                $payment_quote->setMethod("free");
                $payment_quote->save();
            }
        } else {
            $quote->getPayment()->importData($payment);
        }

        // shipping totals may be affected by payment method
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            foreach ($quote->getAllShippingAddresses() as $shippingAddress) {
                $shippingAddress->setCollectShippingRates(true);
            }
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }
        $this->quoteRepository->save($quote);
        return $this;
    }

    /**
     * Create orders per each quote address
     *
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     * @throws \Exception
     */
    public function createOrders($cart)
    {
		$this->loggers("createOrders | cart =" . $cart->getId());
        $orderIds = [];
        $this->_validate($cart);
        $shippingAddresses = $cart->getAllShippingAddresses();
        $orders = [];

        if ($cart->hasVirtualItems()) {
            $shippingAddresses[] = $cart->getBillingAddress();
        }

        try {
            foreach ($shippingAddresses as $address) {
                $order = $this->_prepareOrder($address, $cart);
				$this->loggers("createOrders | [cart - " . $cart->getId()."] | order increment Id =" . $order->getIncrementId());
                $promo_list[$order->getIncrementId()] = [
                    "aw_store_credit_amount" => $address->getData('aw_store_credit_amount'),
                    "base_grand_total" => $address->getData('base_grand_total'),
                    "aw_reward_points_amount" => $address->getData('aw_reward_points_amount'),
                    "aw_giftcard_amount" => $address->getData('aw_giftcard_amount')
                ];
                
                $orders[] = $order;

            }

            $paymentProviderCode = $cart->getPayment()->getMethod();
            $placeOrderService = $this->placeOrderFactory->create($paymentProviderCode);
            $exceptionList = $placeOrderService->place($orders);
            $this->logExceptions($exceptionList);

            /** @var OrderInterface[] $failedOrders */
            $failedOrders = [];
            /** @var OrderInterface[] $successfulOrders */
            $successfulOrders = [];
            foreach ($orders as $order) {
                if (isset($exceptionList[$order->getIncrementId()])) {
                    $failedOrders[] = $order;
                } else {
					$this->loggers("createOrders | [cart - " . $cart->getId()."] | success order ID =" . $order->getId());
                    $successfulOrders[] = $order;
                }
            }

            $placedAddressItems = [];
            $countFeeOrder = 1;
            $fraction_aw_store_credit_amount = 0;
            $fraction_aw_reward_points_amount = 0;
            $fraction_aw_giftcard_amount = 0;
            foreach ($successfulOrders as $order) {
                $grand_total = $promo_list[$order->getIncrementId()]['base_grand_total'];
                $order->setBaseSubtotal($order->getSubtotal());
                $order->save();
                if ($this->moduleManager->isEnabled('Amasty_Extrafee')) {
                    $grandtotal = $grand_total - round((float) $order->getSubTotalInclTax());

                    if ($order->getShippingAmount() > 0) {
                        $grandtotal = $grandtotal - round($order->getShippingAmount());
                    }

                    if ($order->getDiscountAmount() != 0) {
                        $grandtotal = $grandtotal - round($order->getDiscountAmount());
                    }

                    if ($order->getBaseAwRewardPointsAmount() != 0) {
                        $grandtotal = $grandtotal - round($order->getBaseAwRewardPointsAmount());
                    }

                    if ($order->getBaseAwStoreCreditAmount() != 0) {
                        $grandtotal = $grandtotal - round($order->getBaseAwStoreCreditAmount());
                    }

                    $fee = $grandtotal;
                    $order->setGrandTotal(($grand_total - $fee));
                    $order->setBaseGrandTotal(($grand_total - $fee));
                    $grand_total = $grand_total - $fee;
                    
                    $feeOrderCollection = $this->feeOrderCollectionFactory->create();
                    $feeOrderCollection->addFilterByOrderId($order->getId());
                    $feeOrderCollection->joinFees();
    
                    foreach ($feeOrderCollection->getItems() as $feeOrder) {
                        if($countFeeOrder < count($successfulOrders)){
                            $order->setGrandTotal(($grand_total + floor($feeOrder->getTotalAmount()/count($shippingAddresses))));
                            $order->setBaseGrandTotal(($grand_total + floor($feeOrder->getBaseTotalAmount()/count($shippingAddresses))));
                            $order->save();
                            $grand_total = $grand_total + floor($feeOrder->getTotalAmount()/count($shippingAddresses));
                            $feeOrder->setTotalAmount(floor($feeOrder->getTotalAmount()/count($shippingAddresses)));
                            $feeOrder->setBaseTotalAmount(floor($feeOrder->getBaseTotalAmount()/count($shippingAddresses)));
                            $feeOrder->save();
                        } else {
                            $totalAmountFee = $feeOrder->getTotalAmount() - (floor($feeOrder->getTotalAmount()/count($shippingAddresses)) * count($shippingAddresses));
                            $totalBaseAmountFee = $feeOrder->getBaseTotalAmount() - (floor($feeOrder->getBaseTotalAmount()/count($shippingAddresses)) * count($shippingAddresses));
                            $order->setGrandTotal(($grand_total + $totalAmountFee + floor($feeOrder->getTotalAmount()/count($shippingAddresses))));
                            $order->setBaseGrandTotal(($grand_total + $totalBaseAmountFee + floor($feeOrder->getBaseTotalAmount()/count($shippingAddresses))));
                            $order->save();
                            $grand_total = $grand_total + $totalAmountFee + floor($feeOrder->getTotalAmount()/count($shippingAddresses));
                            $feeOrder->setTotalAmount($totalAmountFee + floor($feeOrder->getTotalAmount()/count($shippingAddresses)));
                            $feeOrder->setBaseTotalAmount($totalBaseAmountFee + floor($feeOrder->getBaseTotalAmount()/count($shippingAddresses)));
                            $feeOrder->save();
                        }
                    }

                    $payment = $order->getPayment();
                    $paymentMethod = $payment->getMethod();
                    if($paymentMethod == "free"){
                        $feeCollectionWithOrder = $this->feeInvoiceCollectionFactory->create()->addFieldToFilter("order_id", $order->getId());
                        foreach ($feeCollectionWithOrder->getItems() as $feeWithOrder) {
                            if($countFeeOrder < count($successfulOrders)){
                                $feeWithOrder->setTotalAmount(floor($feeWithOrder->getTotalAmount()/count($shippingAddresses)));
                                $feeWithOrder->setBaseTotalAmount(floor($feeWithOrder->getBaseTotalAmount()/count($shippingAddresses)));
                                $feeWithOrder->save();
                            } else {
                                $totalAmountFee = $feeWithOrder->getTotalAmount() - (floor($feeWithOrder->getTotalAmount()/count($shippingAddresses)) * count($shippingAddresses));
                                $totalBaseAmountFee = $feeWithOrder->getBaseTotalAmount() - (floor($feeWithOrder->getBaseTotalAmount()/count($shippingAddresses)) * count($shippingAddresses));
                                $feeWithOrder->setTotalAmount($totalAmountFee + floor($feeWithOrder->getTotalAmount()/count($shippingAddresses)));
                                $feeWithOrder->setBaseTotalAmount($totalBaseAmountFee + floor($feeWithOrder->getBaseTotalAmount()/count($shippingAddresses)));
                                $feeWithOrder->save();
                            }
                        }
                    }
                }
                
                if($cart->getData("aw_use_store_credit") == true){

                    $whole_aw_store_credit_amount = floor(abs($promo_list[$order->getIncrementId()]['aw_store_credit_amount']));
                    $fraction_aw_store_credit_amount += abs($promo_list[$order->getIncrementId()]['aw_store_credit_amount']) - $whole_aw_store_credit_amount;
                    if($countFeeOrder == count($successfulOrders)) {
                        $aw_store_credit_amount = round($whole_aw_store_credit_amount + $fraction_aw_store_credit_amount);
                    } else {
                        $aw_store_credit_amount = round($whole_aw_store_credit_amount);
                    }

                    $order->setGrandTotal($grand_total - abs($aw_store_credit_amount));
                    $order->setBaseGrandTotal($grand_total - abs($aw_store_credit_amount));
                    $order->setAwUseStoreCredit($cart->getAwUseStoreCredit());
                    $order->setAwStoreCreditAmount(-$aw_store_credit_amount);
                    $order->setBaseAwStoreCreditAmount(-$aw_store_credit_amount);
                    $order->save();
                    $grand_total = $grand_total - abs($aw_store_credit_amount);

                    $this->executeStoreCredit($order);
                }

                if($cart->getData("aw_use_reward_points") == true){

                    $whole_aw_reward_points_amount = floor(abs($promo_list[$order->getIncrementId()]['aw_reward_points_amount']));
                    $fraction_aw_reward_points_amount += abs($promo_list[$order->getIncrementId()]['aw_reward_points_amount']) - $whole_aw_reward_points_amount;
                    if($countFeeOrder == count($successfulOrders)) {
                        $aw_reward_points_amount = round($whole_aw_reward_points_amount + $fraction_aw_reward_points_amount);
                    } else {
                        $aw_reward_points_amount = round($whole_aw_reward_points_amount);
                    }

                    $order->setGrandTotal(($grand_total - abs($aw_reward_points_amount)));
                    $order->setBaseGrandTotal(($grand_total - abs($aw_reward_points_amount)));
                    $order->setAwUseRewardPoints($cart->getAwUseRewardPoints());
                    $order->setAwRewardPointsAmount(-$aw_reward_points_amount);
                    $order->setBaseAwRewardPointsAmount(-$aw_reward_points_amount);
                    $order->setAwRewardPoints(abs($aw_reward_points_amount));
                    $description = abs($aw_reward_points_amount)." Reward Points";
                    $order->setAwRewardPointsDescription($description);
                    $order->save();
                    $grand_total = $grand_total - abs($aw_reward_points_amount);

                    $this->executeRewardPoints($order);
                }

                if($cart->getData("aw_giftcard_amount") > 0){

                    /** @var Quote $quote */
                    $quote = $cart;
                    $address = $quote->isVirtual()
                        ? $quote->getBillingAddress()
                        : $quote->getShippingAddress();
                    
                    $giftcardId = null;
                    $giftcardList = array();
                    if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getAwGiftcardCodes()) {
                        $giftcardList = array_keys($quote->getExtensionAttributes()->getAwGiftcardCodes());
                    }
                    // Apply gift card
                    $whole_aw_giftcard_amount = floor(abs($promo_list[$order->getIncrementId()]['aw_giftcard_amount']));
                    $fraction_aw_giftcard_amount += abs($promo_list[$order->getIncrementId()]['aw_giftcard_amount']) - $whole_aw_giftcard_amount;
                    if($countFeeOrder == count($successfulOrders)) {
                        $aw_giftcard_amount = round($whole_aw_giftcard_amount + $fraction_aw_giftcard_amount);
                    } else {
                        $aw_giftcard_amount = round($whole_aw_giftcard_amount);
                    }

                    $giftcardAmountUsed = $aw_giftcard_amount > 0 ? $aw_giftcard_amount : null;
                    $order->setGrandTotal($grand_total - abs($aw_giftcard_amount));
                    $order->setBaseGrandTotal($grand_total - abs($aw_giftcard_amount));
                    $order->setAwGiftcardAmount($giftcardAmountUsed);
                    $order->setBaseAwGiftcardAmount($giftcardAmountUsed);
                    $order->save();
                    $grand_total = $grand_total - abs($aw_giftcard_amount);
                    $balance = 0;
                    
                    foreach ($giftcardList as $key => $giftcardQuoteId) {
                        $giftcardId = $quote->getExtensionAttributes()->getAwGiftcardCodes()[$giftcardQuoteId]->getGiftcardId();
                        $giftcard = $quote->getExtensionAttributes()->getAwGiftcardCodes()[$giftcardQuoteId];
                        
                        if ($balance !== 'done' && $aw_giftcard_amount > 0) {
                            $baseGiftcardAmount = $balance !== 0 ? $balance : $aw_giftcard_amount;
                            $balance = $this->executeGiftcard($order, $giftcardId, $baseGiftcardAmount);
                        }
                    }
                }

                $payment = $order->getPayment();
                $paymentMethod = $payment->getMethod();
                if($paymentMethod == "free"){
                    $invoice = $payment->getCreatedInvoice();
                    if ($invoice) {
                        $invoice->setGrandTotal(0);
                        $invoice->setBaseGrandTotal(0);

                        if($cart->getData("aw_use_store_credit") == true){
                            $invoice->setAwUseStoreCredit($order->getAwUseStoreCredit());
                            $invoice->setBaseAwStoreCreditAmount(-(abs($order->getBaseAwStoreCreditAmount())));
                            $invoice->setAwStoreCreditAmount(-(abs($order->getAwStoreCreditAmount())));
                        }
                        
                        if($cart->getData("aw_use_reward_points") == true){
                            $invoice->setAwUseRewardPoints($order->getAwUseRewardPoints());
                            $invoice->setAwRewardPoints($order->getAwRewardPoints());
                            $invoice->setAwRewardPointsDescription(__('%1 Reward Points', $order->getAwRewardPoints()));
                            $invoice->setBaseAwRewardPointsAmount(-(abs($order->getBaseAwRewardPointsAmount())));
                            $invoice->setAwRewardPointsAmount(-(abs($order->getAwRewardPointsAmount())));
                        }

                        $invoice->save();
                    }
                    $order->setTotalPaid(0);
                    $order->setBaseTotalPaid(0);
                    $order->save();
                }

                $orderIds[$order->getId()] = $order->getIncrementId();
                if ($order->getCanSendNewEmailFlag()) {
                    $this->orderSender->send($order);
                }
                $placedAddressItems = $this->getPlacedAddressItems($order);
                $countFeeOrder++;
            }

            $addressErrors = [];
            if (!empty($failedOrders)) {
                $this->removePlacedItemsFromQuote($shippingAddresses, $placedAddressItems, $cart);
                $addressErrors = $this->getQuoteAddressErrors(
                    $failedOrders,
                    $shippingAddresses,
                    $exceptionList
                );
            } else {
                $this->_checkoutSession->setLastQuoteId($cart->getId());
                $cart->setIsActive(false);
                $this->quoteRepository->save($cart);
            }

            $this->_session->setOrderIds($orderIds);
            $this->_session->setAddressErrors($addressErrors);

            return $this;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get order IDs created during checkout
     *
     * @param bool $asAssoc
     * @return array
     */
    public function getOrderIds($asAssoc = false)
    {
        $idsAssoc = $this->_session->getOrderIds();
        if ($idsAssoc !== null) {
            return $asAssoc ? $idsAssoc : array_keys($idsAssoc);
        }
        return [];
    }

    /**
     * Get quote address errors.
     *
     * @param OrderInterface[] $orders
     * @param \Magento\Quote\Model\Quote\Address[] $addresses
     * @param \Exception[] $exceptionList
     * @return string[]
     * @throws NotFoundException
     */
    private function getQuoteAddressErrors(array $orders, array $addresses, array $exceptionList): array
    {
        $addressErrors = [];
        foreach ($orders as $failedOrder) {
            if (!isset($exceptionList[$failedOrder->getIncrementId()])) {
                throw new NotFoundException(__('Exception for failed order not found.'));
            }
            $addressId = $this->searchQuoteAddressId($failedOrder, $addresses);
            $addressErrors[$addressId] = $exceptionList[$failedOrder->getIncrementId()]->getMessage();
        }

        return $addressErrors;
    }

    /**
     * Returns quote address id that was assigned to order.
     *
     * @param OrderInterface $order
     * @param \Magento\Quote\Model\Quote\Address[] $addresses
     *
     * @return int
     * @throws NotFoundException
     */
    private function searchQuoteAddressId(OrderInterface $order, array $addresses): int
    {
        $items = $order->getItems();
        $item = array_pop($items);
        foreach ($addresses as $address) {
            foreach ($address->getAllItems() as $addressItem) {
                if ($addressItem->getQuoteItemId() == $item->getQuoteItemId()) {
                    return (int)$address->getId();
                }
            }
        }

        throw new NotFoundException(__('Quote address for failed order ID "%1" not found.', $order->getEntityId()));
    }

    /**
     * Remove successfully placed items from quote.
     *
     * @param \Magento\Quote\Model\Quote\Address[] $shippingAddresses
     * @param int[] $placedAddressItems
     * @return void
     */
    private function removePlacedItemsFromQuote(array $shippingAddresses, array $placedAddressItems, $cart)
    {
		$this->loggers("removePlacedItemsFromQuote | cart =" . $cart->getId());
        foreach ($shippingAddresses as $address) {
			$this->loggers("removePlacedItemsFromQuote | [cart - " . $cart->getId()."] | address =" . $address->getId());
            foreach ($address->getAllItems() as $addressItem) {
				$this->loggers("removePlacedItemsFromQuote | [cart - " . $cart->getId()."] | address Item =" . $addressItem->getId());
				$this->loggers("removePlacedItemsFromQuote | [cart - " . $cart->getId()."] | address Item Quote Id =" . $addressItem->getQuoteItemId());
                if (in_array($addressItem->getQuoteItemId(), $placedAddressItems)) {
                    if ($addressItem->getProduct()->getIsVirtual()) {
                        $addressItem->isDeleted(true);
                    } else {
                        $address->isDeleted(true);
                    }

                    $this->decreaseQuoteItemQty($addressItem->getQuoteItemId(), $addressItem->getQty(), $cart);
                }
            }
        }
        $this->save($cart);
    }

    /**
     * Decrease quote item quantity.
     *
     * @param int $quoteItemId
     * @param int $qty
     * @return void
     */
    private function decreaseQuoteItemQty(int $quoteItemId, int $qty, $cart)
    {
        $quoteItem = $cart->getItemById($quoteItemId);
        if ($quoteItem) {
            $newItemQty = $quoteItem->getQty() - $qty;
			$this->loggers("decreaseQuoteItemQty | [cart - " . $cart->getId()."] | quote qty =" . $quoteItem->getQty());
			$this->loggers("decreaseQuoteItemQty | [cart - " . $cart->getId()."] | qty =" . $qty);
			$this->loggers("decreaseQuoteItemQty | [cart - " . $cart->getId()."] | new qty =" . $newItemQty);
            if ($newItemQty > 0) {
                $quoteItem->setQty($newItemQty);
            } else {
				$this->loggers("decreaseQuoteItemQty | quote item id =" . $quoteItem->getId());
                $cart->removeItem($quoteItem->getId());
                $cart->setIsMultiShipping(1);
            }
        }
    }

    /**
     * Returns placed address items
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getPlacedAddressItems(OrderInterface $order): array
    {
        $placedAddressItems = [];
        foreach ($this->getQuoteAddressItems($order) as $key => $quoteAddressItem) {
            $placedAddressItems[$key] = $quoteAddressItem;
        }
		$this->loggers("getPlacedAddressItems | [order ID - " . $order->getId()."] | quote id =" . json_encode($placedAddressItems, 1));
        return $placedAddressItems;
    }

    /**
     * Returns quote address item id.
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getQuoteAddressItems(OrderInterface $order): array
    {
        $placedAddressItems = [];
        foreach ($order->getItems() as $orderItem) {
            $placedAddressItems[] = $orderItem->getQuoteItemId();
        }
		$this->loggers("getQuoteAddressItems | [order ID - " . $order->getId()."] |  quote id =" . json_encode($placedAddressItems, 1));
        return $placedAddressItems;
    }

    /**
     * Logs exceptions.
     *
     * @param \Exception[] $exceptionList
     * @return void
     */
    private function logExceptions(array $exceptionList)
    {
        foreach ($exceptionList as $exception) {
            $this->logger->critical($exception);
        }
    }

    /**
     * Prepare order based on quote address
     *
     * @param   \Magento\Quote\Model\Quote\Address $address
     * @return  \Magento\Sales\Model\Order
     * @throws  \Magento\Checkout\Exception
     */
    protected function _prepareOrder(\Magento\Quote\Model\Quote\Address $address, $cart)
    {
        $quote = $cart;
        $quote->unsReservedOrderId();
        $quote->reserveOrderId();
        // $quote->collectTotals();
		$this->loggers("_prepareOrder | [cart - " . $cart->getId()."] | quote id =" . $quote->getId());
        $order = $this->_orderFactory->create();
        $this->dataObjectHelper->mergeDataObjects(
            \Magento\Sales\Api\Data\OrderInterface::class,
            $order,
            $this->quoteAddressToOrder->convert($address)
        );
        
        $shippingMethodCode = $address->getShippingMethod();
        if ($shippingMethodCode) {
            $rate = $address->getShippingRateByCode($shippingMethodCode);
            $shippingPrice = $rate->getPrice();
        } else {
            $shippingPrice = $order->getShippingAmount();
        }
        $store = $order->getStore();
        $amountPrice = $store->getBaseCurrency()
            ->convert($shippingPrice, $store->getCurrentCurrencyCode());
        $order->setBaseShippingAmount($shippingPrice);
        $order->setShippingAmount($amountPrice);
		$this->loggers("_prepareOrder | [cart - " . $cart->getId()."] | amountPrice =" . $amountPrice);
		$this->loggers("_prepareOrder | [cart - " . $cart->getId()."] | shippingPrice =" . $shippingPrice);
        $order->setQuote($quote);
        $order->setBillingAddress($this->quoteAddressToOrderAddress->convert($quote->getBillingAddress()));

        if ($address->getAddressType() == 'billing') {
            $order->setIsVirtual(1);
        } else {
            $order->setShippingAddress($this->quoteAddressToOrderAddress->convert($address));
            $order->setShippingMethod($address->getShippingMethod());
        }

        $order->setPayment($this->quotePaymentToOrderPayment->convert($quote->getPayment()));
        if ($this->priceCurrency->round($address->getGrandTotal()) == 0) {
            $order->getPayment()->setMethod('free');
        }

        foreach ($address->getAllItems() as $item) {
            $_quoteItem = $item->getQuoteItem();
            if (!$_quoteItem) {
                throw new \Magento\Checkout\Exception(
                    __("The item isn't found, or it's already ordered.")
                );
            }
            $item->setProductType(
                $_quoteItem->getProductType()
            )->setProductOptions(
                $_quoteItem->getProduct()->getTypeInstance()->getOrderOptions($_quoteItem->getProduct())
            );
            $orderItem = $this->quoteItemToOrderItem->convert($item);
            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($_quoteItem->getParentItem()->getId()));
            }
            $order->addItem($orderItem);
        }
		$this->loggers("_prepareOrder | [cart - " . $cart->getId()."] | order id =" . $order->getId());
        return $order;
    }

    /**
     * Validate quote data
     *
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _validate($cart)
    {
        $quote = $cart;
		$this->loggers("start");
		$this->loggers("_validate | [cart - " . $cart->getId()."] | quote id =" . $quote->getId());
        /** @var $paymentMethod \Magento\Payment\Model\Method\AbstractMethod */
        $paymentMethod = $quote->getPayment()->getMethodInstance();
        if (!$paymentMethod->isAvailable($quote)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("The payment method isn't selected. Enter the payment method and try again.")
            );
        }

        $addresses = $quote->getAllShippingAddresses();
        foreach ($addresses as $address) {
            $addressValidation = $address->validate();
            if ($addressValidation !== true) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Verify the shipping address information and continue.')
                );
            }
            $method = $address->getShippingMethod();
            $rate = $address->getShippingRateByCode($method);
            if (!$method || !$rate) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Set shipping methods for all addresses. Verify the shipping methods and try again.')
                );
            }

            // Checks if a country id present in the allowed countries list.
            if (!in_array($address->getCountryId(), $this->allowedCountryReader->getAllowedCountries())) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Some addresses can't be used due to the configurations for specific countries.")
                );
            }
        }
        $addressValidation = $quote->getBillingAddress()->validate();
        if ($addressValidation !== true) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Verify the billing address information and continue.')
            );
        }
		$this->loggers("end");
		$this->loggers("_validate | [cart - " . $cart->getId()."] | quote id =" . $quote->getId());
        return $this;
    }

    public function executeRewardPoints($order)
    {
		$this->loggers("executeRewardPoints | order ID =" . $order->getId());
        $storeId = $order->getStoreId();
        $store = $this->storeManagerInterface->getStore($storeId);
        $website_id = $store->getWebsiteId();

        $data = array (
            'customer_selections' => '[{"customer_id":"'.$order->getData("customer_id").'","customer_name":"'.$order->getData("customer_firstname").' '.$order->getData("customer_lastname").'","customer_email":"'.$order->getData("customer_email").'","website_id":"'.$website_id.'","position":'.$website_id.',"record_id":"'.$order->getData("customer_id").'"}]',
            'customers_listing' => 
            array (
              0 => 
              array (
                'entity_id' => $order->getData("customer_id"),
              ),
            ),
            'balance' => '-'.$order->getData("aw_reward_points"),
            'comment_to_customer' => 'Spent reward points on order #'.$order->getData("increment_id"),
            'comment_to_admin' => '',
            'expire_in_days' => '',
            'expire' => 'expire_in_x_days',
            'expiration_date' => '',
            'website_id' => $website_id,
        );
		$this->loggers("executeRewardPoints | [order ID - " . $order->getId()."] | data =" . json_encode($data, 1));
        if ($data) {
            $data = $this->prepareData($data);
            $this->dataPersistor->set('transaction', $data);
            $data = $this->dataProcessor->filter($data);
            $this->processSaveRewardPoint($data);
            $this->dataPersistor->clear('transaction');
        }
    }

    public function executeStoreCredit($order)
    {
		$this->loggers("executeStoreCredit | order ID =" . $order->getId());
        $storeId = $order->getStoreId();
        $store = $this->storeManagerInterface->getStore($storeId);
        $website_id = $store->getWebsiteId();

        $data = array (
            'customer_selections' => 
            array (
              0 => 
              array (
                'customer_id' => $order->getData("customer_id"),
                'customer_name' => $order->getData("customer_firstname").'" "'.$order->getData("customer_lastname"),
                'customer_email' => $order->getData("customer_email"),
                'website_id' => $website_id,
                'position' => $website_id,
                'record_id' => $order->getData("customer_id"),
              ),
            ),
            'customers_listing' => 
            array (
              0 => 
              array (
                'entity_id' => $order->getData("customer_id"),
              ),
            ),
            'balance' => $order->getData("aw_store_credit_amount"),
            'comment_to_customer' => 'Spent Store Credit on order #'.$order->getData("increment_id"),
            'comment_to_admin' => '',
            'website_id' => $website_id,
        );
		$this->loggers("executeStoreCredit | [order ID - " . $order->getId()."] | data =" . json_encode($data, 1));
        if ($data) {
            $this->dataPersistor->set('transaction', $data);
            $data = $this->dataProcessor->filter($data);
            $this->processSaveStoreCredit($data);
            $this->dataPersistor->clear('transaction');
        }
    }

    public function executeGiftcard($order, $giftcardId, $baseGiftcardAmount)
    {
        $storeId = $order->getStoreId();
        $store = $this->storeManagerInterface->getStore($storeId);
        $website_id = $store->getWebsiteId();

        $giftcardCode = $this->giftcardRepository->get($giftcardId);
        $balanceLeft = $giftcardCode->getBalance() - $baseGiftcardAmount;
        $balanceUse = $balanceLeft;
        if ($balanceLeft < 0) {
            $balanceUse = $giftcardCode->getBalance();
        } else {
            $balanceUse = $baseGiftcardAmount;
        }

        $giftcardCode->setBalance($giftcardCode->getBalance() - $baseGiftcardAmount);
        $connection = $this->resourceConnection->getConnection();
        
        $data = [
            'giftcard_id' => $giftcardId,
            'order_id' => $order->getId(),
            'base_giftcard_amount' => $balanceUse,
            'giftcard_amount' => $balanceUse
        ];
        $this->loggers("executeGiftcard | [order ID - " . $order->getId()."] | data =" . json_encode($data, 1));
        $connection->insert('aw_giftcard_order', $data);

        /** @var HistoryEntityInterface $orderHistoryEntityObject */
        $orderHistoryEntityObject = $this->historyEntityFactory->create();
        $orderHistoryEntityObject
            ->setEntityType(SourceHistoryEntityType::ORDER_ID)
            ->setEntityId($order->getEntityId())
            ->setEntityLabel($order->getIncrementId());

        /** @var HistoryActionInterface $historyObject */
        $historyObject = $this->historyActionFactory->create();
        $historyObject
            ->setActionType(SourceHistoryCommentAction::APPLIED_TO_ORDER)
            ->setEntities([$orderHistoryEntityObject]);

        $giftcardCode->setCurrentHistoryAction($historyObject);
        $this->giftcardRepository->save($giftcardCode);

        if ($balanceLeft < 0) {
            $balanceLeft = abs($balanceLeft);
        } else {
            $balanceLeft = 'done';
        }

        return $balanceLeft;
    }

    /**
     * Prepare form data
     *
     * @param array $data
     * @return array
     */
    private function prepareData($data)
    {
        if (isset($data['customer_selections'])) {
            $data['customer_selections'] = json_decode($data['customer_selections'], true);
        }
        return $data;
    }

    /**
     * Process save transaction
     *
     * @param array $data
     * @throws LocalizedException
     * @return void
     */
    private function processSaveRewardPoint(array $data)
    {
        $customerSelection = $this->dataProcessor->customerSelectionFilter($data);

        if (!empty($customerSelection)) {
            foreach ($customerSelection as $transactionData) {
                $this->customerRewardPointsService->resetCustomer();
                $this->customerRewardPointsService->saveAdminTransaction($transactionData);
            }
        }
    }

    /**
     * @param array $data
     * @throws LocalizedException
     * @return void
     */
    private function processSaveStoreCredit(array $data)
    {
		$this->loggers("processSaveStoreCredit | data = " . json_encode($cart->getId(), 1));
        $customerSelection = $this->dataProcessor->customerSelectionFilter($data);

        if (!empty($customerSelection)) {
            foreach ($customerSelection as $transactionData) {
                $this->customerStoreCreditService->resetCustomer();
                $this->customerStoreCreditService->saveAdminTransaction($transactionData);
            }
        }
    }

    /**
     * Remove item from address
     *
     * @param int $addressId
     * @param int $itemId
     * @return \Magento\Multishipping\Model\Checkout\Type\Multishipping
     */
    public function removeAddressItem($addressId, $itemId, $cart)
    {
		$this->loggers("removeAddressItem | cart Id = " . $cart->getId());
		$this->loggers("removeAddressItem | [cart - " . $cart->getId()."] | address Id = " . $addressId);
        $address = $cart->getAddressById($addressId);

        /* @var $address \Magento\Quote\Model\Quote\Address */
        if ($address) {
            $item_id_data = '';
            foreach ($address->getAllItems() as $item) {
				$this->loggers("removeAddressItem | [cart - " . $cart->getId()."] | quote_item_id = " . $item->getData('quote_item_id'));
                if($itemId == $item->getData('quote_item_id')){
                    $item_id_data = $item->getData('address_item_id');
					$this->loggers("removeAddressItem | [cart - " . $cart->getId()."] | quote_item_id = " . $item_id_data);
                    $item = $address->getValidItemById($item_id_data);
                    if ($item) {
                        if ($item->getQty() > 1 && !$item->getProduct()->getIsVirtual()) {
                            $item->setQty($item->getQty() - 1);
                        } else {
                            $address->removeItem($item->getId());
                        }
        
                        /**
                         * Require shipping rate recollect
                         */
                        $address->setCollectShippingRates((bool)$this->getCollectRatesFlag());
        
                        if (count($address->getAllItems()) == 0) {
                            $address->isDeleted(true);
                        }
        
                        $quoteItem = $cart->getItemById($item->getQuoteItemId());
                        if ($quoteItem) {
                            $newItemQty = $quoteItem->getQty() - 1;
                            if ($newItemQty > 0 && !$item->getProduct()->getIsVirtual()) {
                                $quoteItem->setQty($quoteItem->getQty() - 1);
                            } else {
                                $cart->removeItem($quoteItem->getId());
                            }
                        }
                        $this->save($cart);
                    }
                }
            }

        }
        return $this;
    }
	
	private function loggers($message)
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info("Icube\CartOrderLog\Model\Checkout\Multiseller::" . $message);
	}
}