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
namespace Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\PostData\ProcessorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Website
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor
 */
class Website implements ProcessorInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var WebsiteInterfaceFactory
     */
    private $websiteFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param RequestInterface $request
     * @param WebsiteInterfaceFactory $websiteFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $request,
        WebsiteInterfaceFactory $websiteFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->websiteFactory = $websiteFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data)
    {
        $websiteId = $website = $this->request->getParam('website', false);
        try {
            $website = $this->storeManager->getWebsite($websiteId);
        } catch (LocalizedException $e) {
            $website = $this->websiteFactory->create();
        }
        $data[AttributeInterface::WEBSITE] = $website;

        return $data;
    }
}
