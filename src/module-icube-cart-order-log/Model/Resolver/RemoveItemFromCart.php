<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\CartOrderLog\Model\Resolver;

use Swiftoms\Multiseller\Model\Resolver\RemoveItemFromCart as OriginalClass;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;

/**
 * @inheritdoc
 */
class RemoveItemFromCart extends OriginalClass
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var MaskedQuoteIdToQuoteId
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var ArgumentsProcessorInterface
     */
    private $argsSelection;

    /**
     * @param GetCartForUser $getCartForUser
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId
     * @param ArgumentsProcessorInterface $argsSelection
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        CartItemRepositoryInterface $cartItemRepository,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        ArgumentsProcessorInterface $argsSelection,
        \Swiftoms\Multiseller\Model\Checkout\Multiseller $multiseller,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->cartItemRepository = $cartItemRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->argsSelection = $argsSelection;
        $this->multiseller = $multiseller;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $processedArgs = $this->argsSelection->process($info->fieldName, $args);
        if (empty($processedArgs['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }
        $maskedCartId = $processedArgs['input']['cart_id'];
        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $maskedCartId])
            );
        }

        if (empty($processedArgs['input']['cart_item_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_item_id" is missing.'));
        }
        $itemId = $processedArgs['input']['cart_item_id'];

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
		
        try {
            $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

            $this->logger("Customer ID - " . $context->getUserId() . " || Cart ID - " . $maskedCartId." || Quote Id - ".$cart->getId());
		    $this->logger("[Quote ID - ". $cart->getId()."] || SKU - " . json_encode($itemId));

            if($cart->getIsMultiShipping() == true){
                $cart->setIsMultiShipping(false);
                $cart->save();
                $i = 1;
                if(count($cart->getAllShippingAddresses()) > 1){
                    $address_count = count($cart->getAllShippingAddresses());
                    foreach($cart->getAllShippingAddresses() as $address){
                        if($i < $address_count){
                            $address->delete();
                        }
                        $i++;
                    }
                }
            }
            $is_multiseller = $this->scopeConfig->getValue(
                'swiftoms_multiseller/configurations/enable_oms_multiseller',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );

            if ($is_multiseller == true) {
                $addresses = $cart->getAllShippingAddresses();
                foreach ($addresses as $address) {
                    $addressId = $address->getId();
                    if ($addressId && $itemId && count($addresses) > 1) {
                        $this->multiseller->removeAddressItem($addressId, $itemId, $cart);
                    } else {
                        $this->cartItemRepository->deleteById($cartId, $itemId);
                    }
                }
            }else{
                $this->cartItemRepository->deleteById($cartId, $itemId);
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('The cart doesn\'t contain the item'));
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }
	
	private function logger($message)
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info("Icube\CartOrderLog\Model\Resolver\RemoveItemFromCart::" . $message);
	}
}
