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
namespace Aheadworks\CustomerAttributes\Block\Adminhtml\Attribute\Edit\Button;

use Magento\Backend\Block\Widget\Context;
use Aheadworks\CustomerAttributes\Api\AttributeRepositoryInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class Delete
 * @package Aheadworks\CustomerAttributes\Block\Adminhtml\Attribute\Edit\Button
 */
class Delete extends AbstractButton implements ButtonProviderInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param Context $context
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        Context $context,
        AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($context);
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $attributeId = $this->context->getRequest()->getParam(AttributeInterface::ATTRIBUTE_ID);
        if ($attributeId) {
            try {
                $attribute = $this->attributeRepository->getById($attributeId);
                if ($attribute->getIsUserDefined()) {
                    $data = [
                        'label' => __('Delete'),
                        'class' => 'delete',
                        'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to do this?')
                            . '\', \'' . $this->getUrl(
                                '*/*/delete',
                                [AttributeInterface::ATTRIBUTE_ID => $attributeId]
                            ) . '\')',
                        'sort_order' => 20,
                    ];
                }
            } catch (NoSuchEntityException $e) {
                $data = [];
            }
        }

        return $data;
    }
}
