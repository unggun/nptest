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
namespace Aheadworks\CustomerAttributes\Controller\Customer;

use Aheadworks\CustomerAttributes\Model\Attribute\File\Provider as FileProvider;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Url\DecoderInterface;

/**
 * Class ViewFile
 * @package Aheadworks\CustomerAttributes\Controller\Customer
 */
class ViewFile extends Action
{
    /**
     * @var DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var FileProvider
     */
    private $fileProvider;

    /**
     * @param Context $context
     * @param DecoderInterface $urlDecoder
     * @param FileProvider $fileProvider
     */
    public function __construct(
        Context $context,
        DecoderInterface $urlDecoder,
        FileProvider $fileProvider
    ) {
        parent::__construct($context);
        $this->urlDecoder  = $urlDecoder;
        $this->fileProvider = $fileProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        if ($file = $this->getRequest()->getParam('image', null)) {
            $file = $this->urlDecoder->decode($file);
            return $this->fileProvider->read($file);
        } elseif ($file = $this->getRequest()->getParam('file', null)) {
            $file = $this->urlDecoder->decode($file);
            return $this->fileProvider->download($file);
        } else {
            throw new NotFoundException(__('Filename is missing.'));
        }
    }
}
