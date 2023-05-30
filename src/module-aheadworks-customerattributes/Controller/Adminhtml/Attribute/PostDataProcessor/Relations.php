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
use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Class Relations
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor
 */
class Relations implements ProcessorInterface
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var AttributeRelationInterfaceFactory
     */
    private $relationFactory;

    /**
     * @param DataObjectHelper $dataObjectHelper
     * @param AttributeRelationInterfaceFactory $relationFactory
     */
    public function __construct(
        DataObjectHelper $dataObjectHelper,
        AttributeRelationInterfaceFactory $relationFactory
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->relationFactory = $relationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data)
    {
        if (!empty($data[AttributeInterface::ATTRIBUTE_RELATIONS])) {
            $resultRelations = [];
            foreach ((array)$data[AttributeInterface::ATTRIBUTE_RELATIONS] as $relationData) {
                $relation = $this->relationFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $relation,
                    $relationData,
                    AttributeRelationInterface::class
                );
                $resultRelations[] = $relation;
            }
            $data[AttributeInterface::ATTRIBUTE_RELATIONS] = $resultRelations;
        }

        return $data;
    }
}
