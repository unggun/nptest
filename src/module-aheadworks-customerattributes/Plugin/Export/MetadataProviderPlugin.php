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

namespace Aheadworks\CustomerAttributes\Plugin\Export;

use Aheadworks\CustomerAttributes\Model\Export\OptionsLoader;
use Magento\Ui\Model\Export\MetadataProvider;

/**
 * Class MetadataProviderPlugin
 * @package Aheadworks\CustomerAttributes\Plugin\Export
 */
class MetadataProviderPlugin
{
    /**
     * @var OptionsLoader
     */
    private $optionsLoader;

    /**
     * @param OptionsLoader $optionsLoader
     */
    public function __construct(
        OptionsLoader $optionsLoader
    ) {
        $this->optionsLoader = $optionsLoader;
    }

    /**
     * Add options for exported attributes
     *
     * @param MetadataProvider $subject
     * @param array $options
     * @return array
     */
    public function afterGetOptions(MetadataProvider $subject, $options)
    {
        return array_merge($options, $this->optionsLoader->getExportAttributesOptions());
    }
}
