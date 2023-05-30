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
namespace Aheadworks\CustomerAttributes\Observer;

use Aheadworks\CustomerAttributes\Block\Customer\Address\FormHtmlProcessor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Block\Address\Edit as AddressEdit;

/**
 * Class BlockToHtmlAfter
 * @package Aheadworks\CustomerAttributes\Observer
 */
class BlockToHtmlAfter implements ObserverInterface
{
    /**
     * @var FormHtmlProcessor
     */
    private $formHtmlProcessor;

    /**
     * @param FormHtmlProcessor $formHtmlProcessor
     */
    public function __construct(
        FormHtmlProcessor $formHtmlProcessor
    ) {
        $this->formHtmlProcessor = $formHtmlProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Observer $observer)
    {
        /** @var AddressEdit $block */
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof AddressEdit) {
            $transport = $observer->getEvent()->getTransport();
            $transport->setHtml($this->formHtmlProcessor->processHtml($block, $transport->getHtml()));
        }

        return $this;
    }
}
