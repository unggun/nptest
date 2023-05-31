<?php

declare(strict_types=1);

namespace Icube\CustomMultiseller\Model\Resolver;

use Icube\ReviewGraphql\Model\Converter\Review\ToDataModel as ReviewConverter;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory as ReviewCollectionFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Shipment;
use Icube\TrackOrder\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Get product review resolver
 * Override of \Icube\SalesGraphQl\Model\Resolver\CustomerOrderDetail
 */
class CustomerOrderDetail implements ResolverInterface
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ReviewConverter
     */
    private $reviewConverter;

    /**
     * @var ReviewCollectionFactory
     */
    private $reviewCollectionFactory;

    /**
     * @var Shipment
     */
    private $Shipment;

    /**
     * @var Data
     */
    private $getHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CustomerOrderDetail constructor.
     *
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Helper\Product
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ReviewConverter $reviewConverter
     * @param ReviewCollectionFactory $collectionFactory
     * @param Shipment $Shipment
     * @param Data $getHelper
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Helper\Product $helperProduct,
        \Icube\Gosend\Helper\Data $gosendHelper,
        CategoryRepositoryInterface $categoryRepository,
        ReviewConverter $reviewConverter,
        ReviewCollectionFactory $collectionFactory,
        Shipment $shipment,
        Data $getHelper,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productFactory = $productFactory;
        $this->helperProduct = $helperProduct;
        $this->gosendHelper = $gosendHelper;
        $this->categoryRepository = $categoryRepository;
        $this->reviewConverter = $reviewConverter;
        $this->reviewCollectionFactory = $collectionFactory;
        $this->_shipment = $shipment;
        $this->_getHelper = $getHelper;
        $this->_storeManager = $storeManager;
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $order  = $this->orderRepository->get($value['id']);

        $order['billing_address'] = $order->getBillingAddress();

        if (empty($order['billing_address'])) {
            $order['billing_address']['street'] = [] ;
        } else {
            $order['billing_address']['street'] = $order['billing_address']->getStreet();
        }

        $order['shipping_address'] = $order->getShippingAddress();

        if (empty($order['shipping_address'])) {
            $order['shipping_address'] = [] ;
        } else {
            $order['shipping_address']['street'] = $order['billing_address']->getStreet();
        }

        /*start getshipping detail from 3rd party */
        $shipment   = $order->getShipmentsCollection()->getItems();
        $trackNumber = "";
        $shippingTitle = "";
        $track_result['data_detail'] = "";

        $track_go = $order->getGosendBookingNo();
        $track_grab = $order->getGrabDeliveryId();

        if (isset($track_go) || isset($track_grab)) {
            if (strpos(strtolower($order->getShippingDescription()), 'go') !== false) {
                $checkGosend = $this->gosendHelper->checkStatus($track_go);
                $replaceResult = str_replace('"', "'", $checkGosend);
                $trackNumber = $track_go;
                $track_result['data_detail'] = $replaceResult;
            } elseif (strpos(strtolower($order->getShippingDescription()), 'grabexpress') !== false) {
                $track_result['data_detail'] = $this->_storeManager->getStore()->getBaseUrl().'grabexpress/booking/checkstatus?deliveryid='.$track_grab;
            }

            $dataTracking[] = [
                "track_number" => $trackNumber,
                "trackorder_type" => $shippingTitle,
                "data_detail" => $track_result["data_detail"]
            ];
        } elseif (count($shipment) > 0) {
            foreach ($shipment as $shipmentDetail) {
                foreach ($shipmentDetail->getAllTracks() as $tracking) {
                    $shippingTitle       = $tracking->getTitle();
                    if ($tracking->getTrackNumber() != '') {
                        $trackNumber = $tracking->getTrackNumber();

                        if (strpos(strtolower($shippingTitle), "logistix") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            $results = $track;
                            $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                        } elseif (strpos(strtolower($shippingTitle), "jne") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            $results = $track;
                            $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                        } elseif (strpos(strtolower($shippingTitle), "rpx") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            $results = $track;
                            $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                        } elseif (strpos(strtolower($shippingTitle), "anteraja") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            $results = $track;
                            $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                        } elseif (strpos(strtolower($shippingTitle), "sap") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            $results = $track;
                            $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                        } elseif (strpos(strtolower($shippingTitle), "shipperid") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            if (isset($track['data']['order']['tracking'])) {
                                $results = $track['data']['order']['tracking'];
                                $results2 = str_replace("'", "\'", json_encode($results));
                                $track_result['data_detail'] = str_replace('"', "'", $results2);
                            } else {
                                $results = $track;
                                $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                            }
                        } elseif (strpos(strtolower($shippingTitle), "popaket") !==false) {
                            $track = $this->_getHelper->getDataTracking($shippingTitle, $trackNumber);
                            if (isset($track['data']['tracking_history'])) {
                                $results = $track['data']['tracking_history'];
                                $results2 = str_replace("'", "\'", json_encode($results));
                                $track_result['data_detail'] = str_replace('"', "'", $results2);
                            } else {
                                $results = $track;
                                $track_result['data_detail'] = str_replace('"', "'", json_encode($results));
                            }
                        }

                        $dataTracking[] = [
                            "track_number" => $trackNumber,
                            "trackorder_type" => $shippingTitle,
                            "data_detail" => $track_result["data_detail"]
                        ];
                    }
                }
            }
            if (empty($dataTracking)) {
                $dataTracking[] = [
                    "track_number" => "Track number not found.",
                    "data_detail" => "Sorry, There is no Tracking Available."
                ];
            }
        } else {
            $dataTracking[] = [
                "track_number" => "Track number not found.",
                "data_detail" => "Sorry, There is no Tracking Available."
            ];
        }

        /*end of check shipping 3rd party*/
        $order['shipping_methods'] = [
            "shipping_description" => $order->getShippingDescription(),
            "shipping_detail" => $dataTracking
        ];

        $paymentInformation = $order->getPayment()->getAdditionalInformation();

        $order['payment']['payment_additional_info'] = [
            "method_title" => @$paymentInformation['method_title'],
            "due_date" => @$paymentInformation['due_date'],
            "virtual_account" => @$paymentInformation['virtual_account'],
            "transaction_id" => @$paymentInformation['transaction_id'],
            "transaction_time" => @$paymentInformation['transaction_time']
        ];

        foreach ($order->getAllItems() as $items) {
            $product = $this->productFactory->create()->load($items->getProductId());
            $caregoryIds = $product->getCategoryIds();

            $categories = array_map(function ($item) {
                return $this->categoryRepository->get($item)->getData();
            }, $caregoryIds);

            $categories = array_filter($categories, function ($item) {
                return $item['is_active'];
            });

            $items['image_url'] = $this->helperProduct->getImageUrl($product);
            $items['quantity_and_stock_status'] = $product['quantity_and_stock_status'];
            $items['categories'] = $categories;

            $sku = $product->getSku();
            $review = $this->getReview($sku);
            $sellerId = $product->getSellerId();

            $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
            $themeTable = $this->resource->getTableName('icube_multiseller');
            $joinTable = $this->resource->getTableName('icube_sellerconfig');
            $sql = sprintf("SELECT im.*,is2.value model,is3.value sla FROM %s im
                LEFT JOIN %s is2 ON is2.seller_id = im.id AND is2.type = 'model'
                LEFT JOIN %s is3 ON is3.seller_id = im.id AND is3.type = 'sla_delivery'
                WHERE im.id = :id", $themeTable, $joinTable, $joinTable);
            $seller_data = $connection->fetchAssoc($sql, [':id' => $sellerId]);

            if (!empty($seller_data)) {
                $items['seller_name'] = $seller_data[$sellerId]["name"];
                $items['seller_type'] = $seller_data[$sellerId]['model'] ?? '';
                $items["seller_sla_delivery"] = $seller_data[$sellerId]['sla'] ?? '';
            }
            $items['seller_id'] = $sellerId;
            $items['rating'] = $review;
        }

        $data = $order->getData();
        $data['coupon']['is_use_coupon'] = null !== $order->getCouponCode() ? 1 : 0;
        $data['coupon']['code'] = $order->getCouponCode();
        $data['coupon']['rule_name'] = $order->getCouponRuleName();
        $result[] = $data;

        return $result;
    }

    private function getReview($sku)
    {
        $collection = $this->reviewCollectionFactory->create();
        $collection->addStoreData();
        $collection->addFieldToFilter('sku', $sku);
        $collection->addStatusFilter(1);
        $collection->addRateVotes();
        $total = $collection->getSize();
        $rating = 0;

        foreach ($collection as $productReview) {
            $productReview->setCreatedAt($productReview->getReviewCreatedAt());
            $reviewData = $this->reviewConverter->toDataModel($productReview);
            if (!empty($reviewData["ratings"])) {
                $rating += (float) $reviewData["ratings"][0]['value'];
            }
        }

        $rating = $total != 0 ? $rating/$total : 0;

        return [
            "value" => $rating,
            "total" => $total,
        ];
    }

    private function __shipment($shipmentId)
    {
        $orderdetails = $this->_shipment->loadByIncrementId($shipmentId);
        return $orderdetails;
    }
}
