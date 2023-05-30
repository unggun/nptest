<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Icube\CustomMultiseller\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;
use Magento\Framework\App\ResourceConnection;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * @inheritdoc
 */
class PlaceOrder implements ResolverInterface
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CheckCartCheckoutAllowance
     */
    private $checkCartCheckoutAllowance;

    /**
     * @param GetCartForUser $getCartForUser
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckCartCheckoutAllowance $checkCartCheckoutAllowance
     * @param ResourceConnection $resource
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        CheckCartCheckoutAllowance $checkCartCheckoutAllowance,
        ResourceConnection $resource,
        \Swiftoms\Multiseller\Model\Checkout\Multiseller $multiseller,
        SessionManagerInterface $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Icube\CustomMultiseller\Model\Resolver\SellerCart $sellerCart,
        ?PaymentProcessingRateLimiterInterface $paymentRateLimiter = null
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->resource = $resource;
        $this->multiseller = $multiseller;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->sellerCart = $sellerCart;
        $this->paymentRateLimiter = $paymentRateLimiter
            ?? ObjectManager::getInstance()->get(PaymentProcessingRateLimiterInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (isset($_SERVER['Checkout_Token'])) {
            $token_header = trim($_SERVER["Checkout_Token"]);
        } else if (isset($_SERVER['HTTP_CHECKOUT_TOKEN'])) {
            $token_header = trim($_SERVER["HTTP_CHECKOUT_TOKEN"]);
        }

        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $args['input']['cart_id'];

        if (!isset($token_header) || empty($token_header)) {
            throw new GraphQlInputException(__('Required parameter "token" is missing.'));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        $token = $cart->getData('token');

        if ($token_header != $token) {
            throw new GraphQlInputException(__('Token is wrong.'));
        }

        $this->checkCartCheckoutAllowance->execute($cart);

        if ((int)$context->getUserId() === 0) {
            if (!$cart->getCustomerEmail()) {
                throw new GraphQlInputException(__("Guest email for cart is missing."));
            }
            $cart->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        }

		$this->logger("Customer ID - " . $context->getUserId());
		$this->logger("Cart ID - " . $maskedCartId);
		$rowItem = 0;
        try {
            $is_multiseller = $this->scopeConfig->getValue(
                'swiftoms_multiseller/configurations/enable_oms_multiseller',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if ($is_multiseller == true) {
                $cart->setData("is_multi_shipping", 1);
                $cart->save();

                $sellerStatus = $this->sellerCart->getSellerData($cart, $context->getUserId());
                foreach ($sellerStatus as $key => $item) {
                    if (isset($item['seller_min_order_status'])&&!$item['seller_min_order_status']) {
                        throw new GraphQlInputException(__("Purchase At %1 store require to greater than Rp. %2!", $item['seller_name'] ?? "", $item['seller_min_order'] ?? ""));
                    }
                    if (isset($item['seller_max_order_status'])&&!$item['seller_max_order_status']) {
                        throw new GraphQlInputException(__(
                            "Daily purchase at %1 store cannot greater than Rp. %2!",
                            $item['seller_name'] ?? "",
                            $item['seller_max_order'] ?? ""
                        ));
                    }
                }

                $this->paymentRateLimiter->limit();
                $this->multiseller->createOrders($cart);

                if ($this->session->getAddressErrors()) {
                } else {
                    $this->multiseller->getCheckoutSession()->clearQuote();
                    $this->multiseller->getCheckoutSession()->setDisplaySuccess(true);
                }

                $orderIds = $this->multiseller->getOrderIds();

                foreach ($orderIds as $orderId) {
                    $order = $this->orderRepository->get($orderId);
					$rowSKU = 0;
					$rowItem = 0;
                    foreach ($order->getItems() as $orderItem) {
						$this->logger($orderItem->getData("qty_ordered") . " Qty");
						$this->logger($orderItem->getData("price") . " price");
						$this->logger($orderItem->getPrice() . " Price");
						
						$rowSKU = $orderItem->getData("qty_ordered") * $orderItem->getData("price");
						$rowItem = $rowItem + $rowSKU;
                        foreach ($cart->getItems() as $item) {
							$this->logger("Cart ID - " . $maskedCartId ." || SKU - " . $item->getData("sku"));
                            if ($item->getData("item_id") == $orderItem->getData("quote_item_id") && !empty($item->getData("note"))) {
                                $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                                $themeTable = $this->resource->getTableName('sales_order_item');
                                $sql = "UPDATE " . $themeTable . " SET note = '" . $item->getData("note") . "' where quote_item_id = '" . $orderItem->getData("quote_item_id") . "'";
                                $connection->query($sql);
                            }
                        }
                    }
					
                    $this->logger($order->getGrandTotal() );
                    $this->logger($rowItem);
                    $this->logger("Cart ID - " . $maskedCartId ."|| order ID ".$order->getId()." || increment Id - ".$order->getIncrementId());
					if($order->getGrandTotal() <> $rowItem) {
						$message = [
							"channel" => "C0263AG2JE8",
							"blocks" => [
								[
									"type" => "section",
									"text" => [
										"type" => "mrkdwn",
										"text" => "Channel - Error Item Order *_" . $order->getIncrementId() . "_*"
									]
								],
							],
						];
						
						$this->sendToSlack($message);
					}
					
                    $result[] = [
                        'order' => [
                            'order_number' => $order->getIncrementId(),
                            // @deprecated The order_id field is deprecated, use order_number instead
                            'order_id' => $order->getIncrementId(),
                        ],
                    ];
                }
            } else {
                $orderId = $this->cartManagement->placeOrder($cart->getId());
                $order = $this->orderRepository->get($orderId);
				$rowSKU = 0;
				$rowItem = 0;
				foreach ($order->getItems() as $orderItem) {
					$this->logger($orderItem->getData("qty_ordered") . " Qty");
					$this->logger($orderItem->getData("price") . " Price");
					$rowSKU = $orderItem->getData("qty_ordered") * $orderItem->getData("price");
					$rowItem = $rowItem + $rowSKU;
					$this->logger("SKU - " . $orderItem->getData("sku"));
				}

                $payment = $order->getPayment();
                $paymentMethod = $payment->getMethod();
                if ($paymentMethod == "free") {
                    $invoice = $payment->getCreatedInvoice();
                    $invoice->setGrandTotal(0);
                    $invoice->setBaseGrandTotal(0);

                    if ($cart->getData("aw_use_store_credit") == true) {
                        $invoice->setAwUseStoreCredit($order->getAwUseStoreCredit());
                        $invoice->setBaseAwStoreCreditAmount(- (abs($order->getBaseAwStoreCreditAmount())));
                        $invoice->setAwStoreCreditAmount(- (abs($order->getAwStoreCreditAmount())));
                    }

                    if ($cart->getData("aw_use_reward_points") == true) {
                        $invoice->setAwUseRewardPoints($order->getAwUseRewardPoints());
                        $invoice->setAwRewardPoints($order->getAwRewardPoints());
                        $invoice->setAwRewardPointsDescription(__('%1 Reward Points', $order->getAwRewardPoints()));
                        $invoice->setBaseAwRewardPointsAmount(- (abs($order->getBaseAwRewardPointsAmount())));
                        $invoice->setAwRewardPointsAmount(- (abs($order->getAwRewardPointsAmount())));
                    }

                    $invoice->save();
                    $order->setTotalPaid(0);
                    $order->setBaseTotalPaid(0);
                    $order->save();
                }

                $this->logger($order->getGrandTotal() );
                $this->logger($rowItem);
                $this->logger("[else condition] Cart ID - " . $maskedCartId ."|| order ID ".$order->getId()." || increment Id - ".$order->getIncrementId());
                if($order->getGrandTotal() <> $rowItem) {
					$message = [
						"channel" => "C0263AG2JE8",
						"blocks" => [
							[
								"type" => "section",
								"text" => [
									"type" => "mrkdwn",
                                    "text" => "Channel - Error Item Order *_" . $order->getIncrementId() . "_*"
								]
							]
						],
					];
					
					$this->sendToSlack($message);
				}

                $result[] = [
                    'order' => [
                        'order_number' => $order->getIncrementId(),
                        // @deprecated The order_id field is deprecated, use order_number instead
                        'order_id' => $order->getIncrementId(),
                    ],
                ];
            }
			
            return $result;
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__('Unable to place order: %message', ['message' => $e->getMessage()]), $e);
        }
    }
	
	private function logger($message)
	{
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/swift.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info("Icube\CustomMultiseller\Model\Resolver\PlaceOrder::" . $message);
	}
	
	private function sendToSlack($message)
	{
		$webhook = "https://hooks.slack.com/services/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";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $webhook);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json"
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
		curl_exec($ch);
		curl_close($ch);
	}
}
