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
namespace Aheadworks\CustomerAttributes\Model\ObjectData;

use Magento\Framework\DataObject;

/**
 * Interface ProcessorInterface
 * @package Aheadworks\CustomerAttributes\Model\ObjectData
 */
interface ProcessorInterface
{
    /**
     * Process data before save
     *
     * @param DataObject $object
     * @return DataObject
     */
    public function beforeSave($object);

    /**
     * Process data after load
     *
     * @param DataObject $object
     * @return DataObject
     */
    public function afterLoad($object);
}
