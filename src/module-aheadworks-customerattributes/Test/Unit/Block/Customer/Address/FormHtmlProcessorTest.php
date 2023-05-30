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
namespace Aheadworks\CustomerAttributes\Test\Unit\Block\Customer\Address;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Aheadworks\CustomerAttributes\Block\Customer\Address\FormHtmlProcessor;
use Magento\Customer\Block\Address\Edit as AddressEdit;

/**
 * Class FormHtmlProcessorTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Block\Customer\Address
 */
class FormHtmlProcessorTest extends TestCase
{
    /**
     * @var FormHtmlProcessor
     */
    private $formProcessor;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->formProcessor = $objectManager->getObject(FormHtmlProcessor::class);
    }

    /**
     * Test processHtml method
     *
     * @param string $html
     * @param string $resultHtml
     * @dataProvider processHtmlProvider
     */
    public function testProcessHtml($html, $resultHtml)
    {
        $additionalAttrHtml = '<div>attributes html</div>';
        $relationsHtml = '<div>relations html</div>';
        $blockMock = $this->createMock(AddressEdit::class);

        $blockMock->expects($this->exactly(2))
            ->method('getChildHtml')
            ->willReturnMap([
                ['additional_attributes', true, $additionalAttrHtml],
                ['relation', true, $relationsHtml]
            ]);

        $this->assertEquals($resultHtml, $this->formProcessor->processHtml($blockMock, $html));
    }

    /**
     * @return array
     */
    public function processHtmlProvider()
    {
        return [
            ['', ''],
            ['<div>some html</div>', '<div>some html</div>'],
            [
                '<div><fieldset>some html</fieldset></div>',
                '<div><fieldset>some html</fieldset><div>attributes html</div><div>relations html</div></div>'
            ]
        ];
    }
}
