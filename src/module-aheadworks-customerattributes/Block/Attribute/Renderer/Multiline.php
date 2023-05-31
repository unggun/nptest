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

use Magento\Framework\Data\Form\Element\Multiline as FrameworkMultiline;

/**
 * Class Multiline
 * @package Aheadworks\CustomerAttributes\Block\Attribute\Renderer
 */
class Multiline extends FrameworkMultiline
{
    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {
        return parent::setType('text');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultHtml()
    {
        $html = '';
        $lineCount = $this->getLineCount();

        $html .= '<div class="field ' . ($this->getRequired() ? 'required' : '') . '">' . "\n";
        for ($i = 0; $i < $lineCount; $i++) {
            if ($i == 0) {
                $html .= '<label class="label" for="' .
                    $this->getHtmlId() .
                    $i .
                    '">' .
                    $this->getLabel() .
                    '</label>' .
                    "\n";
                if ($this->getRequired()) {
                    $this->setClass('input-text required-entry');
                }
            } else {
                $this->setClass('input-text');
                $html .= '<label>&nbsp;</label>' . "\n";
            }
            $html .= '<input id="' .
                $this->getHtmlId() .
                $i .
                '" name="' .
                $this->getName() .
                '[' .
                $i .
                ']' .
                '" value="' .
                $this->getEscapedValue(
                    $i
                ) . '"' . $this->serialize(
                    $this->getHtmlAttributes()
                ) . ' />' . "\n";
            if ($i == 0) {
                $html .= $this->getAfterElementHtml();
            }
        }
        $html .= '</div>' . "\n";

        return $html;
    }
}
