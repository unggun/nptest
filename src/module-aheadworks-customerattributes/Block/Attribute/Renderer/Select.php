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

use Magento\Framework\Data\Form\Element\Select as FrameworkSelect;

/**
 * Class Select
 * @package Aheadworks\CustomerAttributes\Block\Attribute\Renderer
 */
class Select extends FrameworkSelect
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultHtml()
    {
        $html = '<div class="' . $this->getClass() . '">' . "\n";
        $html .= $this->getLabelHtml();
        $html .= $this->getElementHtml();
        $html .= '</div>' . "\n";

        return $html;
    }
}
