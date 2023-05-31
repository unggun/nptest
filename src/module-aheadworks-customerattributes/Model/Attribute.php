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
namespace Aheadworks\CustomerAttributes\Model;

use Aheadworks\CustomerAttributes\Api\Data\AttributeInterface;
use Aheadworks\CustomerAttributes\Model\Attribute\Validator\Composite;
use Aheadworks\CustomerAttributes\Model\ObjectData\ProcessorComposite;
use Aheadworks\CustomerAttributes\Model\Source\Attribute\InputType;
use Magento\Customer\Model\Attribute as CustomerAttribute;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Customer\Model\Metadata\AttributeMetadataCache;
use Magento\Eav\Api\Data\AttributeDefaultValueInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\ResourceModel\Helper;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\Product\ReservedAttributeList;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Class Attribute
 * @package Aheadworks\CustomerAttributes\Model\Data
 */
class Attribute extends CustomerAttribute implements AttributeInterface, AttributeDefaultValueInterface
{
    /**
     * @var ProcessorComposite
     */
    private $dataProcessor;

    /**
     * @var Composite
     */
    private $validator;

    /**
     * {@inheritDoc}
     */
    protected $_eventPrefix = 'aw_customer_attributes_attribute';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Config $eavConfig
     * @param TypeFactory $eavTypeFactory
     * @param StoreManagerInterface $storeManager
     * @param Helper $resourceHelper
     * @param UniversalFactory $universalFactory
     * @param AttributeOptionInterfaceFactory $optionDataFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param TimezoneInterface $localeDate
     * @param ReservedAttributeList $reservedAttributeList
     * @param ResolverInterface $localeResolver
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param IndexerRegistry $indexerRegistry
     * @param ProcessorComposite $dataProcessor
     * @param Composite $composite
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param AttributeMetadataCache|null $attributeMetadataCache
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Config $eavConfig,
        TypeFactory $eavTypeFactory,
        StoreManagerInterface $storeManager,
        Helper $resourceHelper,
        UniversalFactory $universalFactory,
        AttributeOptionInterfaceFactory $optionDataFactory,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        TimezoneInterface $localeDate,
        ReservedAttributeList $reservedAttributeList,
        ResolverInterface $localeResolver,
        DateTimeFormatterInterface $dateTimeFormatter,
        IndexerRegistry $indexerRegistry,
        ProcessorComposite $dataProcessor,
        Composite $composite,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        AttributeMetadataCache $attributeMetadataCache = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $eavConfig,
            $eavTypeFactory,
            $storeManager,
            $resourceHelper,
            $universalFactory,
            $optionDataFactory,
            $dataObjectProcessor,
            $dataObjectHelper,
            $localeDate,
            $reservedAttributeList,
            $localeResolver,
            $dateTimeFormatter,
            $indexerRegistry,
            $resource,
            $resourceCollection,
            $data,
            $attributeMetadataCache
        );
        $this->dataProcessor = $dataProcessor;
        $this->validator = $composite;
    }

    /**
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init(AttributeResource::class);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeDelete()
    {
        if (!$this->getIsUserDefined()) {
            throw new CouldNotDeleteException(__('The attribute can\'t be deleted.'));
        }
        return parent::beforeDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        $this->dataProcessor->prepareDataBeforeSave($this);
        return parent::beforeSave();
    }

    /**
     * {@inheritdoc}
     */
    public function afterLoad()
    {
        $this->dataProcessor->prepareDataAfterLoad($this);
        return parent::afterLoad();
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendTypeByInput($type)
    {
        $backendType = null;

        switch ($type) {
            case InputType::IMAGE:
            case InputType::FILE:
                $backendType = 'varchar';
                break;
            case InputType::MULTILINE:
                $backendType = 'text';
                break;
            default:
                $backendType = parent::getBackendTypeByInput($type);
                break;
        }

        return $backendType;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendModelByInput($type)
    {
        $modelClass = null;

        switch ($type) {
            case InputType::DATE:
                $modelClass = Datetime::class;
                break;
            case InputType::MULTISELECT:
                $modelClass = ArrayBackend::class;
                break;
            default:
                break;
        }

        return $modelClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceModelByInput($type)
    {
        $modelClass = null;

        switch ($type) {
            case InputType::BOOL:
                $modelClass = Boolean::class;
                break;
            case InputType::MULTISELECT:
            case InputType::DROPDOWN:
                $modelClass = Table::class;
                break;
            default:
                break;
        }

        return $modelClass;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getInputFilter()
    {
        return $this->getData(self::INPUT_FILTER);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsVisible()
    {
        return $this->getData(self::IS_VISIBLE);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultilineCount()
    {
        return $this->getData(self::MULTILINE_COUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataModel()
    {
        return $this->getData(self::DATA_MODEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsSystem()
    {
        return $this->getData(self::IS_SYSTEM);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsUsedInGrid()
    {
        return $this->getData(self::IS_USED_IN_GRID);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsVisibleInGrid()
    {
        return $this->getData(self::IS_VISIBLE_IN_GRID);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsFilterableInGrid()
    {
        return $this->getData(self::IS_FILTERABLE_IN_GRID);
    }

    /**
     * {@inheritDoc}
     */
    public function getIsSearchableInGrid()
    {
        return $this->getData(self::IS_SEARCHABLE_IN_GRID);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsedInOrderView()
    {
        return $this->getData(self::USED_IN_ORDER_VIEW);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsedInOrderGrid()
    {
        return $this->getData(self::USED_IN_ORDER_GRID);
    }

    /**
     * {@inheritDoc}
     */
    public function setInputFilter($inputFilter)
    {
        return $this->setData(self::INPUT_FILTER, $inputFilter);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultilineCount($multilineCount)
    {
        return $this->setData(self::MULTILINE_COUNT, $multilineCount);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsVisible($isVisible)
    {
        return $this->setData(self::IS_VISIBLE, $isVisible);
    }

    /**
     * {@inheritDoc}
     */
    public function setDataModel($dataModel)
    {
        return $this->setData(self::DATA_MODEL, $dataModel);
    }

    /**
     * {@inheritDoc}
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsSystem($isSystem)
    {
        return $this->setData(self::IS_SYSTEM, $isSystem);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsUsedInGrid($isUsedInGrid)
    {
        return $this->setData(self::IS_USED_IN_GRID, $isUsedInGrid);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsVisibleInGrid($isVisibleInGrid)
    {
        return $this->setData(self::IS_VISIBLE_IN_GRID, $isVisibleInGrid);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsFilterableInGrid($isFilterableInGrid)
    {
        return $this->setData(self::IS_FILTERABLE_IN_GRID, $isFilterableInGrid);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsSearchableInGrid($isSearchableInGrid)
    {
        return $this->setData(self::IS_SEARCHABLE_IN_GRID, $isSearchableInGrid);
    }
    
    /**
     * {@inheritDoc}
     */
    public function setUsedInOrderGrid($usedInOrderGrid)
    {
        return $this->setData(self::USED_IN_ORDER_GRID, $usedInOrderGrid);
    }
    
    /**
     * {@inheritDoc}
     */
    public function setUsedInOrderView($usedInOrderView)
    {
        return $this->setData(self::USED_IN_ORDER_VIEW, $usedInOrderView);
    }

    /**
     * {@inheritDoc}
     */
    public function setUsedInForms($forms)
    {
        return $this->setData(self::USED_IN_FORMS, $forms);
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendLabel()
    {
        return $this->getData(self::FRONTEND_LABEL);
    }

    /**
     * {@inheritDoc}
     */
    public function setFrontendLabel($frontendLabel)
    {
        return $this->setData(self::FRONTEND_LABEL, $frontendLabel);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeRelations()
    {
        return $this->getData(self::ATTRIBUTE_RELATIONS);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttributeRelations($attributeRelations)
    {
        return $this->setData(self::ATTRIBUTE_RELATIONS, $attributeRelations);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityTypeCode()
    {
        if (!$this->getData(self::ENTITY_TYPE_CODE)) {
            $code = $this->getEntityType()->getEntityTypeCode();
            $this->setEntityTypeCode($code);
        }

        return $this->getData(self::ENTITY_TYPE_CODE);
    }

    /**
     * {@inheritDoc}
     */
    public function setEntityTypeCode($code)
    {
        return $this->setData(self::ENTITY_TYPE_CODE, $code);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getValidationRulesBeforeSave()
    {
        return $this->validator;
    }
}
