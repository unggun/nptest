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
namespace Aheadworks\CustomerAttributes\Plugin\Ui\Component;

use Magento\Customer\Ui\Component\ColumnFactory;
use Magento\Ui\Component\Listing\Columns\ColumnInterface;

/**
 * Class ColumnFactoryPlugin
 * @package Aheadworks\CustomerAttributes\Plugin\Ui\Component
 */
class ColumnFactoryPlugin
{
    const TIMEZONE = 'timezone';

    /**
     * @param ColumnFactory $subject
     * @param ColumnInterface $column
     * @return ColumnInterface
     */
    public function afterCreate(ColumnFactory $subject, ColumnInterface $column)
    {
        $config = $column->getConfig();

        if (isset($config[self::TIMEZONE])) {
            $config[self::TIMEZONE] = var_export($config[self::TIMEZONE], true);
            $column->setConfig($config);
        }

        return $column;
    }
}