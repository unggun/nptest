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

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class AbstractRenderer
 * @package Aheadworks\CustomerAttributes\Block\Attribute\Renderer
 */
class AbstractRenderer extends AbstractElement
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultHtml()
    {
        $html = $this->getData('default_html');
        if ($html === null) {
            $html = '<div class="' . $this->getClass() . '">' . "\n";
            $html .= $this->getLabelHtml();
            $html .= $this->getElementHtml();
            $html .= '</div>' . "\n";
        }
        return $html;
    }
}
