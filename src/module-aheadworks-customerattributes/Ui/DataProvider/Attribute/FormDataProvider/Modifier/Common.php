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
namespace Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Api\Data\AttributeDefaultValueInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Class Common
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier
 */
class Common implements ModifierInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        $data[AttributeInterface::ENTITY_TYPE_CODE] = $this->request->getParam(
            'type',
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER
        );
        if (isset($data[AttributeInterface::ATTRIBUTE_ID])) {
            $data['isEdit'] = true;
        }
        if (!empty($data[AttributeInterface::FRONTEND_INPUT])) {
            $defaultValue = isset($data[AttributeDefaultValueInterface::DEFAULT_VALUE])
                ? $data[AttributeDefaultValueInterface::DEFAULT_VALUE]
                : '';
            $defaultValueKey = AttributeDefaultValueInterface::DEFAULT_VALUE
                . '_' . $data[AttributeInterface::FRONTEND_INPUT];
            if ($data[AttributeInterface::FRONTEND_INPUT] == InputType::MULTILINE) {
                $defaultValueKey = AttributeDefaultValueInterface::DEFAULT_VALUE . '_' . InputType::TEXT;
            }
            $scopeKey = 'scope_' . AttributeDefaultValueInterface::DEFAULT_VALUE;
            $scopeValue = isset($data[$scopeKey]) ? $data[$scopeKey] : $defaultValue;

            $data[$defaultValueKey] = $defaultValue;
            $data['scope_' . $defaultValueKey] = $scopeValue;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
