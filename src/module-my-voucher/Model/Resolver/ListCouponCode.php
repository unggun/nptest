<?php

declare(strict_types=1);

namespace Icube\MyVoucher\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Cms\Api\BlockRepositoryInterface;
use Icube\CartRuleBanner\Model\RuleFactory as RuleBannerFactory;
use Magento\Store\Model\StoreManagerInterface;


class ListCouponCode implements ResolverInterface
{

    public $count = 0;
    public $totalPages = 0;

    public function __construct(
        CouponFactory $couponFactory,
        RuleFactory $ruleFactory,
        BlockRepositoryInterface $blockRepositoryInterface,
        RuleBannerFactory $ruleBannerFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->couponFactory = $couponFactory;
        $this->ruleFactory = $ruleFactory;
        $this->blockRepositoryInterface = $blockRepositoryInterface;
        $this->ruleBannerFactory = $ruleBannerFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        try {

            $couponModel = $this->getCouponCollection($args);

            $couponsArray = [];
            $size = 0;
            foreach ($couponModel as $key => $couponData) {
                $size++;
                $ruleId = $couponData->getRuleId();
                $couponsArray[$key]['bannerImage'] = $this->getBanner($couponData);
                $couponsArray[$key]['name'] = $couponData->getName();
                $couponsArray[$key]['startDate'] = $couponData->getFromDate();
                $couponsArray[$key]['dueDate'] = $couponData->getToDate();
                $couponsArray[$key]['couponCode'] = $couponData->getCode();
                $couponsArray[$key]['couponQty'] = $couponData->getUsesPerCoupon();
                $couponsArray[$key]['detail'] = $couponData->getDescription();
            }

            return [
                'total_count' => $this->count,
                'items' => $couponsArray,
                'page_info' => [
                    'page_size' => $size,
                    'current_page' => $couponModel->getCurPage(),
                    'total_pages' => $this->totalPages,
                ]
            ];

        } 
        catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }

    public function getCouponCollection(array $args)
    {
        $today = date('Y-m-d');
        $salesRule = $this->ruleFactory->create();
        $collection = $salesRule->getCollection()
        ->addFieldToFilter('is_active', 1)
        ->addFieldToFilter('to_date',['gteq' => $today])
        ->addFieldToFilter('coupon_type', 2)
        ->addFieldToFilter('use_auto_generation', 0)
        ->setOrder('to_date', 'ASC');

        $this->count = $collection->getSize();
        $this->totalPages = ceil($this->count / $args['pageSize']);
        $collection->setPageSize($args['pageSize'])->setCurPage($args['currentPage']);

        return $collection;
    }

    public function getBanner($couponData)
    {
        if ($couponData->getCartRuleBannerId()) {

            try {

                $ruleBanner = $this->ruleBannerFactory->create()->load($couponData->getCartRuleBannerId());

                if($ruleBanner) {
                    $cmsBlock = $this->blockRepositoryInterface->getById($ruleBanner->getCmsBlockId());

                    $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA );

                    $image = $this->mappingImage($cmsBlock->getContent());

                    return $mediaUrl.$image;
                }

            } catch (\Exception $e) {

            }
        }
    
        return null;
    }

    public function mappingImage($data)
    {

        $data = trim($data,'[mgz_pagebuilder]');
        $data = trim($data,'[/mgz_pagebuilder]');

        $data = json_decode($data, true);

        if(isset($data['elements'][0]['elements'][0]['elements'][0]['image'])) {
            return $data['elements'][0]['elements'][0]['elements'][0]['image'];
        }

        return null;
    }
}