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
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterfaceFactory;
use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\Converter;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider as AttributeFormDataProvider;
use Magento\Framework\Exception\LocalizedException;
use Aheadworks\CustomerAttributes\Model\PostData\ProcessorComposite as PostDataProcessorComposite;
use Magento\Backend\Model\View\Result\Redirect;

/**
 * Class Save
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute
 */
class Save extends Action
{
    /**
     * {@inheritdoc}
     */
    const ADMIN_RESOURCE = 'Aheadworks_CustomerAttributes::attributes';

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var PostDataProcessorComposite
     */
    private $postDataProcessor;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @param Context $context
     * @param Converter $converter
     * @param AttributeRepositoryInterface $attributeRepository
     * @param DataPersistorInterface $dataPersistor
     * @param PostDataProcessorComposite $postDataProcessorComposite
     */
    public function __construct(
        Context $context,
        Converter $converter,
        AttributeRepositoryInterface $attributeRepository,
        DataPersistorInterface $dataPersistor,
        PostDataProcessorComposite $postDataProcessorComposite
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->attributeRepository = $attributeRepository;
        $this->dataPersistor = $dataPersistor;
        $this->postDataProcessor = $postDataProcessorComposite;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data = $this->getRequest()->getPostValue()) {
            try {
                $preparedData = $this->postDataProcessor->prepareData($data);
                $attribute = $this->converter->getDataObjectByFormData($preparedData);
                $attribute = $this->attributeRepository->save($attribute);
                $this->dataPersistor->clear(AttributeFormDataProvider::DATA_PERSISTOR_FORM_DATA_KEY);
                $this->messageManager->addSuccessMessage(__('Attribute was successfully saved.'));
                $action = isset($preparedData['action']) ? $preparedData['action'] : false;
                if ($action == 'edit') {
                    $params = [AttributeInterface::ATTRIBUTE_ID => $attribute->getAttributeId()];
                    if ($websiteId = $this->getRequest()->getParam('website', false)) {
                        $params['website'] = $websiteId;
                    }
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        $params
                    );
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the attribute.'));
            }
            $this->dataPersistor->set(AttributeFormDataProvider::DATA_PERSISTOR_FORM_DATA_KEY, $data);
            $attributeId = isset($data[AttributeInterface::ATTRIBUTE_ID])
                ? $data[AttributeInterface::ATTRIBUTE_ID]
                : false;
            if ($attributeId) {
                return $resultRedirect->setPath(
                    '*/*/edit',
                    [AttributeInterface::ATTRIBUTE_ID => $attributeId, '_current' => true]
                );
            }
            return $resultRedirect->setPath('*/*/new', ['_current' => true]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
