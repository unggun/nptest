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
namespace Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute\Formatter;

use Aheadworks\CustomerAttributes\Model\Attribute\Formatter\Date;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class DateTest
 * @package Aheadworks\CustomerAttributes\Test\Unit\Model\Attribute\Formatter
 */
class DateTest extends TestCase
{
    /**
     * @var TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeDateMock;

    /**
     * @var ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeResolverMock;
    
    /**
     * @var Date
     */
    private $formatter;

    /**
     * Init mocks for tests
     *
     * @return void
     */
    public function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->localeDateMock = $this->createMock(TimezoneInterface::class);
        $this->localeResolverMock = $this->createMock(ResolverInterface::class);
        $this->formatter = $objectManager->getObject(
            Date::class,
            [
                'localeDate' => $this->localeDateMock,
                'localeResolver' => $this->localeResolverMock
            ]
        );
    }

    /**
     * Test format method
     *
     * @param string $value
     * @throws \Exception
     * @dataProvider formatProvider
     */
    public function testFormat($value)
    {
        if (is_string($value) && strpos($value, '-') === false) {
            $date = new \DateTime($value);

            $this->localeResolverMock->expects($this->once())
                ->method('getLocale')
                ->willReturn('en_US');
            $this->localeDateMock->expects($this->once())
                ->method('date')
                ->with($value, 'en_US', false, false)
                ->willReturn($date);
            $valueFormatted = $date->format(DateTime::DATETIME_PHP_FORMAT);
        } else {
            $valueFormatted = $value;
        }

        $this->assertEquals($valueFormatted, $this->formatter->format($value));
    }

    /**
     * Test strToTime method
     *
     * @param mixed $value
     * @dataProvider strToTimeProvider
     */
    public function testStrToTime($value)
    {
        $result = strtotime($value);

        $this->assertEquals($result, $this->formatter->strToTime($value));
    }

    /**
     * @return array
     */
    public function formatProvider()
    {
        return [
            ['01/01/2000'],
            ['01-01-2000'],
            [time()]
        ];
    }

    /**
     * @return array
     */
    public function strToTimeProvider()
    {
        return [
            ['01/01/2000'],
            ['some string']
        ];
    }
}
