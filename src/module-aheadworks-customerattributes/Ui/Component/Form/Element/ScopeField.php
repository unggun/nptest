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
namespace Aheadworks\CustomerAttributes\Ui\Component\Form\Element;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class ScopeField
 * @package Aheadworks\CustomerAttributes\Ui\Component\Form\Element
 */
class ScopeField extends Field
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var string
     */
    private $processedScope = '';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param RequestInterface $request
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        RequestInterface $request,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $config = $this->getData('config');
        if ($this->request->getParam('website')) {
            $this->processedScope = $config['dataScope'];
            $config['dataScope'] = 'scope_' . $config['dataScope'];
            $config['service'] = ['template' => 'ui/form/element/helper/service'];
            $this->setData('config', $config);
        }
        parent::prepare();
    }

    /**
     * {@inheritDoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataScope = $this->getData('config/dataScope');

        if ($this->processedScope && isset($dataSource[$this->processedScope])) {
            $dataSource[$dataScope] = $dataSource[$this->processedScope];
        }

        return $dataSource;
    }
}
