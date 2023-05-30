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
namespace Aheadworks\CustomerAttributes\Model\Sales\AttributesData;

use Aheadworks\CustomerAttributes\Model\ResourceModel\Attribute;
use Aheadworks\CustomerAttributes\Model\Sales\Quote;
use Aheadworks\CustomerAttributes\Model\Sales\QuoteFactory;
use Aheadworks\CustomerAttributes\Model\ResourceModel\Sales\Quote as QuoteResource;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class QuotePersistor
 * @package Aheadworks\CustomerAttributes\Model\Sales\AttributesData
 */
class QuotePersistor
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
    }

    /**
     * Save
     *
     * @param AbstractModel $quote
     * @throws AlreadyExistsException
     */
    public function save(AbstractModel $quote)
    {
        /** @var Quote $quoteAttributeModel */
        $quoteAttributeModel = $this->quoteFactory->create();
        $data = $quote->getData();
        $data[Attribute::QUOTE_ID] = $quote->getId();
        $quoteAttributeModel->addData($data);

        $this->quoteResource->save($quoteAttributeModel);
    }

    /**
     * Load
     *
     * @param AbstractModel $quote
     */
    public function load(AbstractModel $quote)
    {
        /** @var Quote $quoteAttributeModel */
        $quoteAttributeModel = $this->quoteFactory->create();
        $this->quoteResource->load($quoteAttributeModel, $quote->getId());
        $quote->addData($quoteAttributeModel->getData());
    }
}
