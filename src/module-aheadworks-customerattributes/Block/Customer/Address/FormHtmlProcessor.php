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
namespace Aheadworks\CustomerAttributes\Block\Customer\Address;

use Magento\Customer\Block\Address\Edit as AddressEdit;

/**
 * Class FormHtmlProcessor
 * @package Aheadworks\CustomerAttributes\Block\Customer\Address
 */
class FormHtmlProcessor
{
    /**
     * Marker string to detect HTML fragment
     */
    const MARKER_STRING = '</fieldset>';

    /**
     * Process form html output
     *
     * @param AddressEdit $block
     * @param string $html
     * @return string
     */
    public function processHtml($block, $html)
    {
        $additionalAttrHtml = $block->getChildHtml('additional_attributes');
        $relationsHtml = $block->getChildHtml('relation');

        if (!empty($html)) {
            $lastPos = strrpos($html, self::MARKER_STRING);
            if ($lastPos !== false) {
                $length = $lastPos + strlen(self::MARKER_STRING);
                $beforeHtml = substr($html, 0, $length);
                $afterHtml = substr($html, $length);
                $html = $beforeHtml . $additionalAttrHtml . $relationsHtml . $afterHtml;
            }
        }

        return $html;
    }
}
