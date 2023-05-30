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
namespace Aheadworks\CustomerAttributes\Model\PostData;

/**
 * Interface ProcessorInterface
 * @package Aheadworks\CustomerAttributes\Model\PostData
 */
interface ProcessorInterface
{
    /**
     * Prepare entity data for save
     *
     * @param array $data
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($data);
}
