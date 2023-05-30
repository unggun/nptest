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
 * Class ProcessorComposite
 * @package Aheadworks\CustomerAttributes\Model\ObjectData
 */
class ProcessorComposite
{
    /**
     * @var ProcessorInterface[]
     */
    private $processors;

    /**
     * @param array $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * Prepare entity data before save
     *
     * @param DataObject $object
     * @return DataObject
     */
    public function prepareDataBeforeSave($object)
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof ProcessorInterface) {
                $processor->beforeSave($object);
            }
        }

        return $object;
    }

    /**
     * Prepare entity data after load
     *
     * @param DataObject $object
     * @return DataObject
     */
    public function prepareDataAfterLoad($object)
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof ProcessorInterface) {
                $processor->afterLoad($object);
            }
        }

        return $object;
    }
}
