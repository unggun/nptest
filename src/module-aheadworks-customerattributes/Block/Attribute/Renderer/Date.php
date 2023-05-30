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
namespace Aheadworks\CustomerAttributes\Block\Attribute\Renderer;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Html\Date as FrameworkDate;

/**
 * Class Date
 * @package Aheadworks\CustomerAttributes\Block\Attribute\Renderer
 */
class Date extends AbstractRenderer
{
    /**
     * @var FrameworkDate
     */
    private $dateElement;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param FrameworkDate $date
     * @param TimezoneInterface $localeDate
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        FrameworkDate $date,
        TimezoneInterface $localeDate,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->dateElement = $date;
        $this->localeDate = $localeDate;
    }

    /**
     * {@inheritDoc}
     */
    public function getElementHtml()
    {
        $this->dateElement->setData([
            'name' => $this->getName(),
            'id' => $this->getHtmlId(),
            'value' => $this->getValue(),
            'date_format' => $this->getDateFormat(),
            'change_month' => 'true',
            'change_year' => 'true',
            'show_on' => 'both'
        ]);

        return $this->dateElement->getHtml();
    }

    /**
     * Retrieve value
     *
     * @return string
     */
    private function getValue()
    {
        return $this->localeDate->formatDate($this->getData('value'));
    }

    /**
     * Retrieve date format
     *
     * @return string
     */
    private function getDateFormat()
    {
        return $this->localeDate->getDateFormat();
    }
}
