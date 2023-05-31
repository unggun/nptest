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
namespace Aheadworks\CustomerAttributes\Api\Data;

use Magento\Eav\Api\Data\AttributeInterface as EavAttributeInterface;

/**
 * Interface AttributeInterface
 * @package Aheadworks\CustomerAttributes\Api\Data
 */
interface AttributeInterface extends EavAttributeInterface
{
    /**#@+
     * Customer attribute keys
     */
    const INPUT_FILTER = 'input_filter';
    const IS_VISIBLE = 'is_visible';
    const MULTILINE_COUNT = 'multiline_count';
    const DATA_MODEL = 'data_model';
    const SORT_ORDER = 'sort_order';
    const IS_SYSTEM = 'is_system';
    const IS_USED_IN_GRID = 'is_used_in_grid';
    const IS_VISIBLE_IN_GRID = 'is_visible_in_grid';
    const IS_FILTERABLE_IN_GRID = 'is_filterable_in_grid';
    const IS_SEARCHABLE_IN_GRID = 'is_searchable_in_grid';
    const USED_IN_FORMS = 'used_in_forms';
    const WEBSITE = 'website';
    /**#@-*/

    /**#@+
     * Additional data attribute keys
     */
    const USED_IN_ORDER_GRID = 'used_in_order_grid';
    const USED_IN_ORDER_VIEW = 'used_in_order_view';
    const ATTRIBUTE_RELATIONS = 'attribute_relations';
    const ENTITY_TYPE_CODE = 'entity_type_code';
    /**#@-*/

    /**
     * Get template used for input (eg "date")
     *
     * @return string
     */
    public function getInputFilter();

    /**
     * Set template used for input (eg "date")
     *
     * @param string $inputFilter
     * @return $this
     */
    public function setInputFilter($inputFilter);

    /**
     * Get is visible on frontend
     *
     * @return bool
     */
    public function getIsVisible();

    /**
     * Set is visible on frontend
     *
     * @param bool $isVisible
     * @return $this
     */
    public function setIsVisible($isVisible);

    /**
     * Number of lines of the attribute value
     *
     * @return int
     */
    public function getMultilineCount();

    /**
     * Set number of lines of the attribute value
     *
     * @param int $multilineCount
     * @return $this
     */
    public function setMultilineCount($multilineCount);

    /**
     * Get data model for attribute
     *
     * @return string
     */
    public function getDataModel();

    /**
     * Get data model for attribute
     *
     * @param string $dataModel
     * @return $this
     */
    public function setDataModel($dataModel);

    /**
     * Get attributes sort order
     *
     * @return int
     */
    public function getSortOrder();

    /**
     * Get attributes sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder);

    /**
     * Get is a system attribute
     *
     * @return bool
     */
    public function getIsSystem();

    /**
     * Set is a system attribute
     *
     * @param bool $isSystem
     * @return $this
     */
    public function setIsSystem($isSystem);

    /**
     * Get is used in customer grid
     *
     * @return bool|null
     */
    public function getIsUsedInGrid();

    /**
     * Get is visible in customer grid
     *
     * @return bool|null
     */
    public function getIsVisibleInGrid();

    /**
     * Get is filterable in customer grid
     *
     * @return bool|null
     */
    public function getIsFilterableInGrid();

    /**
     * Get is searchable in customer grid
     *
     * @return bool|null
     */
    public function getIsSearchableInGrid();

    /**
     * Set used in customer grid
     *
     * @param bool $isUsedInGrid
     * @return $this
     */
    public function setIsUsedInGrid($isUsedInGrid);

    /**
     * Set visible in customer grid
     *
     * @param bool $isVisibleInGrid
     * @return $this
     */
    public function setIsVisibleInGrid($isVisibleInGrid);

    /**
     * Set filterable in customer grid
     *
     * @param bool $isFilterableInGrid
     * @return $this
     */
    public function setIsFilterableInGrid($isFilterableInGrid);

    /**
     * Set searchable in customer grid
     *
     * @param bool $isSearchableInGrid
     * @return $this
     */
    public function setIsSearchableInGrid($isSearchableInGrid);

    /**
     * Get is used in order grid
     *
     * @return bool|null
     */
    public function getUsedInOrderGrid();

    /**
     * Set is used in order grid
     *
     * @param bool $usedInOrderGrid
     * @return $this
     */
    public function setUsedInOrderGrid($usedInOrderGrid);

    /**
     * Get is used in order view
     *
     * @return bool|null
     */
    public function getUsedInOrderView();

    /**
     * Set is used in order view
     *
     * @param bool $usedInOrderView
     * @return $this
     */
    public function setUsedInOrderView($usedInOrderView);

    /**
     * Get used in forms
     *
     * @return bool|null
     */
    public function getUsedInForms();

    /**
     * Set used in forms
     *
     * @param string[] $forms
     * @return $this
     */
    public function setUsedInForms($forms);

    /**
     * Get default frontend label
     *
     * @return string
     */
    public function getFrontendLabel();

    /**
     * Set default frontend label
     *
     * @param string $frontendLabel
     * @return $this
     */
    public function setFrontendLabel($frontendLabel);

    /**
     * Get attribute relations
     *
     * @return \Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface[]|null
     */
    public function getAttributeRelations();

    /**
     * Set attribute relations
     *
     * @param \Aheadworks\CustomerAttributes\Api\Data\AttributeRelationInterface[] $attributeRelations
     * @return $this
     */
    public function setAttributeRelations($attributeRelations);

    /**
     * Get entity type code
     *
     * @return string
     */
    public function getEntityTypeCode();

    /**
     * Set entity type code
     *
     * @param string $code
     * @return $this
     */
    public function setEntityTypeCode($code);
}
