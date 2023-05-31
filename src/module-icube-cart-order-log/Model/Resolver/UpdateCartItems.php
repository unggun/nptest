<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Icube\CartOrderLog\Model\Resolver;

use Swiftoms\Multiseller\Model\Resolver\UpdateCartItems as OriginalClass;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\UpdateCartItems as  UpdateCartItemsProvider;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * @inheritdoc
 */
class UpdateCartItems extends OriginalClass
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var UpdateCartItemsProvider
     */
    private $updateCartItems;

    /**
     * @var ArgumentsProcessorInterface
     */
    private $argsSelection;

    /**
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     * @param UpdateCartItemsProvider $updateCartItems
     * @param ArgumentsProcessorInterface $argsSelection
     * @param ResourceConnection $resource
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        CartRepositoryInterface $cartRepository,
        UpdateCartItemsProvider $updateCartItems,
        ArgumentsProcessorInterface $argsSelection,
        ResourceConnection $resource
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->cartRepository = $cartRepository;
        $this->updateCartItems = $updateCartItems;
        $this->argsSelection = $argsSelection;
        $this->resource = $resource;
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

        if (empty($processedArgs['input']['cart_items'])
            || !is_array($processedArgs['input']['cart_items'])
        ) {
            throw new GraphQlInputException(__('Required parameter "cart_items" is missing.'));
        }

        $cartItems = $processedArgs['input']['cart_items'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        
        $this->logger("Customer ID - " . $context->getUserId() . " || Cart ID - " . $maskedCartId." || Quote Id - ".$cart->getId());
		$this->logger("[Quote ID - ". $cart->getId()."] || SKU - " . json_encode($cartItems));

        try {
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
            $this->updateCartItems->processCartItems($cart, $cartItems);
            foreach($cart->getItems() as $item){
                if($item->getData("item_id") == $processedArgs['input']['cart_items'][0]['cart_item_id'] && isset($processedArgs['input']['note'])){
                    $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                    $themeTable = $this->resource->getTableName('quote_item');
                    $sql = "UPDATE " . $themeTable . " SET note = '" .$processedArgs['input']['note']. "' where item_id = '" . $item->getData("item_id") . "'";
                    $connection->query($sql);
                }
            }
            $this->cartRepository->save($cart);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
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
		$logger->info("Icube\CartOrderLog\Model\Resolver\UpdateCartItems::" . $message);
	}
}
