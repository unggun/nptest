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

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Class Visibility
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute\FormDataProvider\Modifier
 */
class Visibility implements ModifierInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        RequestInterface $request,
        ArrayManager $arrayManager
    ) {
        $this->request = $request;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function modifyMeta(array $meta)
    {
        $entityType = $this->request->getParam('type');
        if ($entityType == AddressMetadataInterface::ENTITY_TYPE_ADDRESS) {
            $fields = [
                'used_in_order_grid',
                'used_in_order_view'
            ];
            foreach ($fields as $field) {
                $optionsPath = 'attribute_properties/children/' . $field . '/arguments/data/config';
                if (!$this->arrayManager->findPath($optionsPath, $meta)) {
                    $meta = $this->arrayManager->set($optionsPath, $meta, []);
                }
                $meta = $this->arrayManager->merge(
                    $optionsPath,
                    $meta,
                    ['componentDisabled' => true]
                );
            }
        }
        return $meta;
    }
}
