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
namespace Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Option;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as OptionCollection;

/**
 * Class Collection
 * @package Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Option
 */
class Collection extends OptionCollection
{
    /**
     * {@inheritDoc}
     */
    protected function _afterLoad()
    {
        $this->attachStoreLabels();
        return parent::_afterLoad();
    }

    /**
     * Attach store labels
     *
     * @return $this
     */
    private function attachStoreLabels()
    {
        $this->attachData(
            'eav_attribute_option_value',
            'option_id',
            'store_labels',
            [
                'option_id',
                'value_id',
                'store_id',
                'label' => 'value'
            ]
        );

        return $this;
    }

    /**
     * Attach data
     *
     * @param string $table
     * @param string $referenceColumn
     * @param string $resultFieldAlias
     * @param array $fields
     * @return $this
     */
    private function attachData($table, $referenceColumn, $resultFieldAlias, $fields)
    {
        $ids = $this->getColumnValues($referenceColumn);
        $fields[] = $referenceColumn;

        if ($ids) {
            $select = $this->getConnection()->select()
                ->from(
                    ['tmp' => $this->getTable($table)],
                    $fields
                )->where($referenceColumn . ' IN (?)', $ids);
            $values = $this->getConnection()->fetchAll($select);

            foreach ($this as $item) {
                $result = [];
                foreach ($values as $valueData) {
                    if ($valueData[$referenceColumn] == $item->getData($referenceColumn)) {
                        $result[] = $valueData;
                    }
                }
                $item->setData($resultFieldAlias, $result);
            }
        }

        return $this;
    }
}
