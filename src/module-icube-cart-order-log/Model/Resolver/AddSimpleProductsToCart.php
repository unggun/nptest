<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\CartOrderLog\Model\Resolver;

use Icube\QuoteGraphQl\Model\Resolver\AddSimpleProductsToCart as OriginalClass;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\AddProductsToCart;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Add simple products to cart GraphQl resolver
 * {@inheritdoc}
 */
class AddSimpleProductsToCart extends OriginalClass
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var AddProductsToCart
     */
    private $addProductsToCart;

    /**
     * @var QuoteMutexInterface
     */
    private $quoteMutex;

    /**
     * @param GetCartForUser $getCartForUser
     * @param AddProductsToCart $addProductsToCart
     * @param QuoteMutexInterface $quoteMutex
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        AddProductsToCart $addProductsToCart,
        QuoteMutexInterface $quoteMutex
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->addProductsToCart = $addProductsToCart;
        $this->quoteMutex = $quoteMutex;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        if (empty($args['input']['cart_items'])
            || !is_array($args['input']['cart_items'])
        ) {
            throw new GraphQlInputException(__('Required parameter "cart_items" is missing'));
        }
        
        return $this->quoteMutex->execute(
            [$args['input']['cart_id']],
            \Closure::fromCallable([$this, 'run']),
            [$context, $args]
        );
    }

    /**
     * Run the resolver.
     *
     * @param ContextInterface $context
     * @param array|null $args
     * @return array[]
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function run($context, ?array $args): array
    {
        $maskedCartId = $args['input']['cart_id'];
        $cartItems = $args['input']['cart_items'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        
		$this->logger("Customer ID - " . $context->getUserId() . " || Cart ID - " . $maskedCartId." || Quote Id - ".$cart->getId());
		$this->logger("[Quote ID - ". $cart->getId()."] || SKU - " . json_encode($cartItems));
        
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
        $this->addProductsToCart->execute($cart, $cartItems);
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
		$logger->info("Icube\CartOrderLog\Model\Resolver\AddSimpleProductsToCart::" . $message);
	}
}
