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
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Class UseDefault
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier
 */
class UseDefault implements ModifierInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $componentNames;

    /**
     * @param RequestInterface $request
     * @param array $componentNames
     */
    public function __construct(
        RequestInterface $request,
        $componentNames = []
    ) {
        $this->request = $request;
        $this->componentNames = $componentNames;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        if ($this->request->getParam('website', false)) {
            if (!isset($data['is_disabled'])) {
                $data['is_disabled'] = [];
            }
            foreach ($this->componentNames as $componentName) {
                $scopeKey = 'scope_' . $componentName;
                $defaultValue = isset($data[$componentName]) ? $data[$componentName] : null;
                $scopeValue =  isset($data[$scopeKey]) ? $data[$scopeKey] : null;
                $data['is_disabled'][$componentName] = ($defaultValue == $scopeValue || $scopeValue === null);
                $data[$scopeKey] = $scopeValue ? $scopeValue : $defaultValue;
            }
        } else {
            $data['is_disabled'] = [AttributeInterface::IS_REQUIRED => true];
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
