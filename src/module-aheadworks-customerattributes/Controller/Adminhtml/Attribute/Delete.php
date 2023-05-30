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
namespace Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;

/**
 * Class Delete
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute
 */
class Delete extends Action
{
    /**
     * {@inheritdoc}
     */
    const ADMIN_RESOURCE = 'Aheadworks_CustomerAttributes::attributes';

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
     * {@inheritDoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $attributeId = (int)$this->getRequest()->getParam(AttributeInterface::ATTRIBUTE_ID);
        if ($attributeId) {
            try {
                $this->attributeRepository->deleteById($attributeId);
                $this->messageManager->addSuccessMessage(__('You deleted the attribute.'));
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage(__('Something went wrong while deleting the attribute.'));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
