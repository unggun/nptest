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

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Ui\Component\Control\Container;

/**
 * Class Save
 * @package Aheadworks\CustomerAttributes\Block\Adminhtml\Attribute\Edit\Button
 */
class Save extends AbstractButton implements ButtonProviderInterface
{
    /**
     * Form target name
     */
    const TARGET_NAME = 'aw_customer_attributes_attribute_form.aw_customer_attributes_attribute_form';

    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => self::TARGET_NAME,
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    $this->prepareParams()
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
            'sort_order' => 60
        ];
    }

    /**
     * Get options
     *
     * @return array
     */
    private function getOptions()
    {
        $options = [];

        foreach ($this->getButtonOptionsData() as $buttonOptionsData) {
            $options[] = [
                'label' => $buttonOptionsData['label'],
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => self::TARGET_NAME,
                                    'actionName' => 'save',
                                    'params' => $buttonOptionsData['params']
                                ]
                            ]
                        ]
                    ]
                ],
            ];
        }

        return $options;
    }

    /**
     * Get button options data
     *
     * @return array
     */
    private function getButtonOptionsData()
    {
        return [
            [
                'label' => __('Save &amp; Continue Edit'),
                'params' => [
                    true,
                    $this->prepareParams(['action' => 'edit'])
                ]
            ]
        ];
    }

    /**
     * Prepare params
     *
     * @param array $params
     * @return array
     */
    private function prepareParams(array $params = [])
    {
        if ($websiteId = $this->context->getRequest()->getParam('website', false)) {
            $params['website'] = $websiteId;
        }

        return $params;
    }
}
