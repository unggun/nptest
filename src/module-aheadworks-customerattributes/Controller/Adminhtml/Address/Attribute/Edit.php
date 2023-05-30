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
namespace Aheadworks\CustomerAttributes\Controller\Adminhtml\Address\Attribute;

use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Edit
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Address\Attribute
 */
class Edit extends Action
{
    /**
     * {@inheritdoc}
     */
    const ADMIN_RESOURCE = 'Aheadworks_CustomerAttributes::address_attributes';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $exception = null;
        $attributeId = (int)$this->getRequest()->getParam(AttributeInterface::ATTRIBUTE_ID);
        if ($attributeId) {
            $this->getRequest()->setParams(['type' => AddressMetadataInterface::ENTITY_TYPE_ADDRESS]);
            try {
                $attribute = $this->attributeRepository->getById($attributeId);
                if ($attribute->getFrontendInput() == InputType::MULTILINE) {
                    throw new LocalizedException(__('Multi Line attributes is not supported.'));
                }
            } catch (LocalizedException $exception) {
                $this->messageManager->addExceptionMessage(
                    $exception,
                    $exception->getMessage()
                );
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addExceptionMessage(
                    $exception,
                    __('Something went wrong while editing the attribute.')
                );
            }
        }

        if ($exception) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage
            ->setActiveMenu('Aheadworks_CustomerAttributes::address_attributes')
            ->getConfig()->getTitle()->prepend(
                $attributeId ? __('Edit Attribute') : __('New Attribute')
            );
        return $resultPage;
    }
}
