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
namespace Aheadworks\CustomerAttributes\Test\Unit\Observer;

use Aheadworks\CustomerAttributes\Observer\BlockToHtmlAfter;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Block\Address\Edit as AddressEdit;
use Aheadworks\CustomerAttributes\Block\Customer\Address\FormHtmlProcessor;

/**
 * Class BlockToHtmlAfterTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Observer
 */
class BlockToHtmlAfterTest extends TestCase
{
    /**
     * @var FormHtmlProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formHtmlProcessorMock;

    /**
     * @var BlockToHtmlAfter
     */
    private $observer;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->formHtmlProcessorMock = $this->createMock(FormHtmlProcessor::class);
        $this->observer = $objectManager->getObject(
            BlockToHtmlAfter::class,
            [
                'formHtmlProcessor' => $this->formHtmlProcessorMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @param AddressEdit|DataObject|\PHPUnit_Framework_MockObject_MockObject|null $blockMock
     * @dataProvider executeProvider
     */
    public function testExecute($blockMock)
    {
        $html = '<div>some html content</div>';
        $processedHtml = '<div>some html content<div>processed</div></div>';
        $callsCount = $blockMock instanceof AddressEdit ? 1 : 0;
        $eventMock = $this->createConfiguredMock(Event::class, ['getBlock' => $blockMock]);
        $observerMock = $this->createConfiguredMock(Observer::class, ['getEvent' => $eventMock]);
        $transportMock = $this->createMock(DataObject::class);

        $eventMock->expects($this->exactly($callsCount))
            ->method('__call')
            ->with('getTransport')
            ->willReturn($transportMock);
        $transportMock->expects($this->exactly($callsCount*2))
            ->method('__call')
            ->willReturnMap([
                ['getHtml', [], $html],
                ['setHtml', [$processedHtml], null]
            ]);
        $this->formHtmlProcessorMock->expects($this->exactly($callsCount))
            ->method('processHtml')
            ->with($blockMock, $html)
            ->willReturn($processedHtml);

        $this->observer->execute($observerMock);
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            [$this->createMock(AddressEdit::class)],
            [$this->createMock(DataObject::class)],
            [null]
        ];
    }
}
