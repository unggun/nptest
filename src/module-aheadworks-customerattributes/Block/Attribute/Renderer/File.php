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
namespace Aheadworks\CustomerAttributes\Block\Attribute\Renderer;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Aheadworks\CustomerAttributes\Model\Attribute\File\Info as FileInfo;
use Magento\Framework\Escaper;

/**
 * Class File
 * @package Aheadworks\CustomerAttributes\Block\Attribute\Renderer
 * @method Attribute getAttribute()
 */
class File extends AbstractRenderer
{
    /**
     * @var FileInfo
     */
    private $fileInfo;

    /**
     * @var string
     */
    private $uId;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param FileInfo $fileInfo
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        FileInfo $fileInfo,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->fileInfo = $fileInfo;
        $this->uId = uniqid();
        $this->setType('file');
    }

    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {
        return parent::setType('file');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultHtml()
    {
        if ($this->getValue()) {
            $html = '<div class="' . $this->getClass() . ' ' . $this->getName()
                . '" data-bind="scope:\'' . $this->getScope() . '\'">' . "\n"
                . $this->getLabelHtml() . "\n"
                . '<!-- ko template: getTemplate() --><!-- /ko -->' . "\n"
                . '<script type="text/x-magento-init">
                    {
                        ".' . $this->getName() . '": {
                            "Magento_Ui/js/core/app": {
                                "components": {
                                    "' . $this->getScope() . '": {
                                        "component": "Aheadworks_CustomerAttributes/js/ui/form/element/file-uploader",
                                        "dataScope": "' . $this->getName() . '",
                                        "config": ' . $this->getComponentConfig() . '
                                    }
                                }
                            }
                        }
                    }
                </script>' . '</div>' . "\n";
        } else {
            $html = parent::getDefaultHtml();
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function getElementHtml()
    {
        return '<div>' . parent::getElementHtml() . '</div>';
    }

    /**
     * Retrieve component config
     *
     * @return string
     * @throws \Exception
     */
    private function getComponentConfig()
    {
        $validation = $this->getRequired() ? ['required-entry' => true] : [];
        return json_encode([
            'label' => $this->getLabel(),
            'value' => $this->getFileData(),
            'validation' => $validation,
            'template' => 'Aheadworks_CustomerAttributes/form/element/uploader/uploader',
            'previewTmpl' => 'Aheadworks_CustomerAttributes/form/element/uploader/preview',
            'visible' => true
        ]);
    }

    /**
     * Retrieve file data
     *
     * @return array
     * @throws \Exception
     * phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    private function getFileData()
    {
        $file = $this->getValue();
        $attribute = $this->getAttribute();

        if (!empty($file) && $this->fileInfo->isExist($file)) {
            $stat = $this->fileInfo->getStat($file);
            return [
                [
                    'file' => $file,
                    'size' => null !== $stat ? $stat['size'] : 0,
                    'url' => $this->fileInfo->getUrl($file, $attribute->getFrontendInput()),
                    'name' => basename($file),
                    'type' => $this->fileInfo->getMimeType($file),
                ],
            ];
        }

        return [];
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->getName() . $this->uId;
    }
}
