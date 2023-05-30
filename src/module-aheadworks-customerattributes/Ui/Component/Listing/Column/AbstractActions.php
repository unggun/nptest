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
namespace Aheadworks\CustomerAttributes\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class AbstractActions
 * @package Aheadworks\CustomerAttributes\Ui\Component\Listing\Column
 */
class AbstractActions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $indexField = $this->getIndexField();
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item[$indexField])) {
                    $item[$name] = $this->getActionsDataForItem($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Retrieve index field name
     *
     * @return string
     */
    protected function getIndexField()
    {
        return $this->getData('config/indexField');
    }

    /**
     * Get actions data
     *
     * @param array $item
     * @return array
     */
    protected function getActionsDataForItem($item)
    {
        $actionsData = [];
        $actionsConfig = $this->getActionsConfig();
        foreach ($actionsConfig as $actionName => $actionConfigData) {
            $currentActionData = $this->getDataForAction($actionConfigData, $item);
            if (!empty($currentActionData)) {
                $actionsData[$actionName] = $currentActionData;
            }
        }
        return $actionsData;
    }

    /**
     * Retrieve item actions config
     *
     * @return array
     */
    protected function getActionsConfig()
    {
        return $this->getData('config/actions');
    }

    /**
     * Get action data for specified item id
     *
     * @param array $actionConfigData
     * @param array $itemData
     * @return array
     */
    protected function getDataForAction($actionConfigData, $itemData)
    {
        $action = [];
        $idKey = $actionConfigData['id_key'];
        $id = $itemData[$idKey];
        if ($id) {
            $action = [
                'href' => $this->urlBuilder->getUrl(
                    $actionConfigData['url_route'],
                    [
                        $this->getParamKey($actionConfigData) => $id,
                    ]
                ),
                'label' => $actionConfigData['label']
            ];
            if (isset($actionConfigData['confirm'])
                && isset($actionConfigData['confirm']['title'])
                && isset($actionConfigData['confirm']['message'])
            ) {
                $action['confirm'] = [
                    'title' => $actionConfigData['confirm']['title'],
                    'message' => $actionConfigData['confirm']['message']
                ];
            }
        }

        return $action;
    }

    /**
     * Get param key
     *
     * @param array $actionConfigData
     * @return string
     */
    protected function getParamKey($actionConfigData)
    {
        return isset($actionConfigData['param_key'])
            ? $actionConfigData['param_key']
            : $this->getIndexField();
    }
}
