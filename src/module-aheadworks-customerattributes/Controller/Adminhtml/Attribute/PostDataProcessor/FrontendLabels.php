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
use Magento\Eav\Api\Data\AttributeFrontendLabelInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Class FrontendLabels
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor
 */
class FrontendLabels implements ProcessorInterface
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var AttributeFrontendLabelInterfaceFactory
     */
    private $labelFactory;

    /**
     * @param DataObjectHelper $dataObjectHelper
     * @param AttributeFrontendLabelInterfaceFactory $labelFactory
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        AttributeFrontendLabelInterfaceFactory $labelFactory
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->labelFactory = $labelFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data)
    {
        if (!empty($data[AttributeInterface::FRONTEND_LABELS])) {
            $resultLabels = [];
            foreach ((array)$data[AttributeInterface::FRONTEND_LABELS] as $labelData) {
                $label = $this->labelFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $label,
                    $labelData,
                    AttributeFrontendLabelInterface::class
                );
                $resultLabels[] = $label;
            }
            $data[AttributeInterface::FRONTEND_LABELS] = $resultLabels;
        }

        return $data;
    }
}
