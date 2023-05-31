<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\CartOrderLog\Model\Resolver;

use Icube\QuoteGraphQl\Model\Resolver\AddProductsToCart as OriginalClass;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\Cart\AddProductsToCart as AddProductsToCartService;
use Magento\Quote\Model\Cart\Data\AddProductsToCartOutput;
use Magento\Quote\Model\Cart\Data\CartItemFactory;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Quote\Model\Cart\Data\Error;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\Processor\ItemDataProcessorInterface;

/**
 * Resolver for addProductsToCart mutation
 *
 * @inheritdoc
 */
class AddProductsToCart extends OriginalClass
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var AddProductsToCartService
     */
    private $addProductsToCartService;

    /**
     * @var ItemDataProcessorInterface
     */
    private $itemDataProcessor;

    /**
     * @param GetCartForUser $getCartForUser
     * @param AddProductsToCartService $addProductsToCart
     * @param  ItemDataProcessorInterface $itemDataProcessor
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        AddProductsToCartService $addProductsToCart,
        ItemDataProcessorInterface $itemDataProcessor
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->addProductsToCartService = $addProductsToCart;
        $this->itemDataProcessor = $itemDataProcessor;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['cartId'])) {
            throw new GraphQlInputException(__('Required parameter "cartId" is missing'));
        }
        if (empty($args['cartItems']) || !is_array($args['cartItems'])
        ) {
            throw new GraphQlInputException(__('Required parameter "cartItems" is missing'));
        }
		
        $maskedCartId = $args['cartId'];
        $cartItemsData = $args['cartItems'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        
		$this->logger("Customer ID - " . $context->getUserId() . " || Cart ID - " . $maskedCartId." || Quote Id - ".$cart->getId());
		$this->logger("[Quote ID - ". $cart->getId()."] || SKU - " . json_encode($cartItemsData));
        
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

        // Shopping Cart validation
        $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

        $cartItems = [];
        foreach ($cartItemsData as $cartItemData) {
            if (!$this->itemIsAllowedToCart($cartItemData, $context)) {
                continue;
            }
            $cartItems[] = (new CartItemFactory())->create($cartItemData);
        }

        /** @var AddProductsToCartOutput $addProductsToCartOutput */
        $addProductsToCartOutput = $this->addProductsToCartService->execute($maskedCartId, $cartItems);
        
        $error = function (Error $error) {
            return $error->getMessage();
        };

        $errorMsg = array_map($error, $addProductsToCartOutput->getErrors());

        if (!empty($errorMsg)){
            throw new GraphQlInputException(__($errorMsg[0]));
        }

        return [
            'cart' => [
                'model' => $addProductsToCartOutput->getCart(),
            ],
            'user_errors' => array_map(
                function (Error $error) {
                    return [
                        'code' => $error->getCode(),
                        'message' => $error->getMessage(),
                        'path' => [$error->getCartItemPosition()]
                    ];
                },
                $addProductsToCartOutput->getErrors()
            )
        ];
    }

    /**
     * Check if the item can be added to cart
     *
     * @param array $cartItemData
     * @param ContextInterface $context
     * @return bool
     */
    private function itemIsAllowedToCart(array $cartItemData, ContextInterface $context): bool
    {
        $cartItemData = $this->itemDataProcessor->process($cartItemData, $context);
        if (isset($cartItemData['grant_checkout']) && $cartItemData['grant_checkout'] === false) {
            return false;
        }

        return true;
    }
	
	private function logger($message)
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info("Icube\CartOrderLog\Model\Resolver\AddProductsToCart::" . $message);
	}
}
