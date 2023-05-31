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
namespace Aheadworks\CustomerAttributes\Model\Attribute\Formatter;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as StdlibDateTime;

/**
 * Class Date
 * @package Aheadworks\CustomerAttributes\Model\Attribute\Formatter
 */
class Date
{
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var StdlibDateTime
     */
    private $dateTime;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @param ResolverInterface $resolver
     * @param TimezoneInterface $localeDate
     * @param StdlibDateTime $dateTime
     */
    public function __construct(
        ResolverInterface $resolver,
        TimezoneInterface $localeDate,
        StdlibDateTime $dateTime
    ) {
        $this->localeDate = $localeDate;
        $this->localeResolver = $resolver;
        $this->dateTime = $dateTime;
    }

    /**
     * Format value to PHP format
     *
     * @param string $value
     * @return string
     */
    public function format($value)
    {
        if (is_string($value) && strpos($value, '-') === false) {
            $value = $this->localeDate->date(
                $value,
                $this->localeResolver->getLocale(),
                false,
                false
            );
            $value = $value->format(DateTime::DATETIME_PHP_FORMAT);
        }

        return $value;
    }

    /**
     * String to timestamp
     *
     * @param string $value
     * @return false|int
     */
    public function strToTime($value)
    {
        return strtotime($value);
    }

    /**
     * Timestamp to String
     *
     * @param int $value
     * @return false|int
     */
    public function timeToDate($value)
    {
        return $this->dateTime->date($value);
    }
}
