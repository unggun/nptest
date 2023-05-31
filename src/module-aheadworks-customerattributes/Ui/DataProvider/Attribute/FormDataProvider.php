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
namespace Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute;

use Aheadworks\CustomerAttributes\Model\Attribute;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\Collection;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Class FormDataProvider
 * @package Aheadworks\CustomerAttributes\Ui\DataProvider\Attribute
 */
class FormDataProvider extends AbstractDataProvider
{
    /**
     * Key for saving and getting form data from data persistor
     */
    const DATA_PERSISTOR_FORM_DATA_KEY = 'aw_customer_attributes_attribute_form';

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param DataPersistorInterface $dataPersistor
     * @param PoolInterface $pool
     * @param UrlInterface $url
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        DataPersistorInterface $dataPersistor,
        PoolInterface $pool,
        UrlInterface $url,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->dataPersistor = $dataPersistor;
        $this->pool = $pool;
        $this->entityType = $request->getParam('type');
        $this->url = $url;
        $data = $this->prepareDataConfig($data);
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create($this->entityType);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $preparedData = [];
        $attributeDataFromForm = $this->dataPersistor->get(self::DATA_PERSISTOR_FORM_DATA_KEY);
        $id = $this->request->getParam($this->getRequestFieldName());

        if (!empty($attributeDataFromForm)) {
            $attributeDataFromForm['from_persistor'] = true;
            $preparedData[$id] = $attributeDataFromForm;
            $this->dataPersistor->clear(self::DATA_PERSISTOR_FORM_DATA_KEY);
        } else {
            /** @var Collection $collection */
            $collection = $this->getCollection();
            if ($website = $this->request->getParam('website', 0)) {
                $collection->addBindParam('scope_website_id', $website);
            }
            $attributes = $collection
                ->addSystemHiddenFilter()
                ->addExcludeHiddenFrontendFilter()
                ->getItems();
            /** @var Attribute $attribute */
            foreach ($attributes as $attribute) {
                if ($id == $attribute->getAttributeId()) {
                    $preparedData[$id] = $this->getPreparedAttributeData($attribute->getData());
                }
            }
        }
        if (!count($preparedData)) {
            $preparedData[$id] = [Attribute::ENTITY_TYPE_CODE => $this->entityType];
        }

        return $preparedData;
    }

    /**
     * {@inheritDoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * Retrieve array with prepared attribute data
     *
     * @param array $attributeData
     * @return array
     * @throws LocalizedException
     */
    private function getPreparedAttributeData($attributeData)
    {
        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $attributeData = $modifier->modifyData($attributeData);
        }

        return $attributeData;
    }

    /**
     * Prepare data config
     *
     * @param array $data
     * @return array
     */
    private function prepareDataConfig($data)
    {
        $config = empty($data['config']) ? [] : $data['config'];
        $url = $this->entityType == AddressMetadataInterface::ENTITY_TYPE_ADDRESS
            ? $this->url->getUrl('aw_customer_attributes/address_attribute/save')
            : $this->url->getUrl('aw_customer_attributes/attribute/save');
        $config['submit_url'] = $url;
        $data['config'] = $config;

        return $data;
    }
}
