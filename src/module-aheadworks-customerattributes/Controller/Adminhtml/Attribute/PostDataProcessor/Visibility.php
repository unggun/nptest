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

use Aheadworks\CustomerAttributes\Model\PostData\ProcessorInterface;
use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Class Visibility
 *
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor
 */
class Visibility implements ProcessorInterface
{
    /**
     * @var array
     */
    private $triggerInputTypes = [
        InputType::FILE,
        InputType::IMAGE
    ];

    /**
     * {@inheritdoc}
     */
    public function process($data)
    {
        if (in_array($data[AttributeInterface::FRONTEND_INPUT], $this->triggerInputTypes)) {
            $data[AttributeInterface::IS_USED_IN_GRID] = 0;
            $data[AttributeInterface::IS_FILTERABLE_IN_GRID] = 0;
            $data[AttributeInterface::IS_SEARCHABLE_IN_GRID] = 0;
            $data[AttributeInterface::USED_IN_ORDER_GRID] = 0;
            $data[AttributeInterface::USED_IN_ORDER_VIEW] = 0;
        }

        if ($data[AttributeInterface::ENTITY_TYPE_CODE] == AddressMetadataInterface::ENTITY_TYPE_ADDRESS) {
            $data[AttributeInterface::USED_IN_ORDER_GRID] = 0;
            $data[AttributeInterface::USED_IN_ORDER_VIEW] = 0;
        }

        return $data;
    }
}
