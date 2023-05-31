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
namespace Aheadworks\CustomerAttributes\Model\Attribute;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterfaceFactory;
use Aheadworks\CustomerAttributes\Model\Attribute;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Class Converter
 * @package Aheadworks\CustomerAttributes\Model\Attribute
 */
class Converter
{
    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @param AttributeInterfaceFactory $attributeFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        AttributeInterfaceFactory $attributeFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * Retrieves data object using model
     *
     * @param Attribute $model
     * @return AttributeInterface
     */
    public function getDataObject($model)
    {
        /** @var AttributeInterface $object */
        $object = $this->attributeFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $object,
            $this->dataObjectProcessor->buildOutputDataArray($model, AttributeInterface::class),
            AttributeInterface::class
        );
        return $object;
    }

    /**
     * Retrieves data object by form data
     *
     * @param array $formData
     * @return AttributeInterface
     */
    public function getDataObjectByFormData($formData)
    {
        /** @var Attribute $object */
        $object = $this->attributeFactory->create();
        $website = isset($formData[AttributeInterface::WEBSITE]) ? $formData[AttributeInterface::WEBSITE] : 0;

        $object
            ->addData($formData)
            ->setWebsite($website);

        return $object;
    }
}
