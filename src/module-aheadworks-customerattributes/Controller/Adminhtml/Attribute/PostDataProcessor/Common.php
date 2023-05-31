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
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Eav\Api\Data\AttributeDefaultValueInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class Common
 * @package Aheadworks\CustomerAttributes\Controller\Adminhtml\Attribute\PostDataProcessor
 */
class Common implements ProcessorInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function process($data)
    {
        $website = $this->request->getParam('website', false);
        $defaultValueKey = AttributeDefaultValueInterface::DEFAULT_VALUE
            . '_' . $data[AttributeInterface::FRONTEND_INPUT];
        if ($data[AttributeInterface::FRONTEND_INPUT] == InputType::MULTILINE) {
            $defaultValueKey = AttributeDefaultValueInterface::DEFAULT_VALUE . '_' . InputType::TEXT;
        }

        //Workaround solution for fix conflict with native customer attributes module
        if (isset($data[AttributeInterface::ENTITY_TYPE_CODE])) {
            $data[AttributeInterface::ENTITY_TYPE_ID] = $data[AttributeInterface::ENTITY_TYPE_CODE];
        }
        if (empty($data[AttributeInterface::ATTRIBUTE_ID])) {
            unset($data[AttributeInterface::ATTRIBUTE_ID]);
        }
        if (!isset($data[AttributeInterface::IS_USER_DEFINED])) {
            $data[AttributeInterface::IS_USER_DEFINED] = 1;
        }
        if (isset($data['use_default']) && $website) {
            foreach ($data['use_default'] as $field => $value) {
                if ($value) {
                    $data['scope_' . $field] = null;
                }
            }
        }
        if (isset($data[$defaultValueKey])) {
            $defaultValue = $data[$defaultValueKey];
            $data[AttributeDefaultValueInterface::DEFAULT_VALUE] = $defaultValue;
            if ($website) {
                $scopeKey = 'scope_' . $defaultValueKey;
                $scopeValue = isset($data[$scopeKey]) ? $data[$scopeKey] : null;
                $data['scope_' . AttributeDefaultValueInterface::DEFAULT_VALUE] = $scopeValue;
            }
        }
        return $data;
    }
}
