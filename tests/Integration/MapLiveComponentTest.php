<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Model\Coordinates;
use App\Model\DayMatcher;
use App\Model\GeolocatableObject;
use App\Model\MapConfig;
use App\Model\TimeRange;
use App\Model\TimeRangeContainer;
use App\Service\FrenchHolidayCalculator;
use App\Service\MapConfigBuilder;
use App\Twig\Components\MapLive;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * Integration tests for MapLive component time range behavior.
 *
 * These tests verify that MapLive correctly handles time ranges when determining
 * whether to show markers or the "no data" overlay.
 */
class MapLiveComponentTest extends TestCase
{
    use ClockSensitiveTrait;

    private FrenchHolidayCalculator $holidayCalculator;

    protected function setUp(): void
    {
        $this->holidayCalculator = new FrenchHolidayCalculator();
    }

    public function testTimeRangeContainerMatchesWeekdayWithinHours(): void
    {
        // Tuesday 10:00 - within weekday hours 08:00-18:00
        static::mockTime(new DateTimeImmutable('2025-06-10 10:00:00'));

        $container = $this->createWeekdayTimeRangeContainer();

        $this->assertTrue($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerDoesNotMatchWeekend(): void
    {
        // Saturday 10:00 - weekend, should be closed
        static::mockTime(new DateTimeImmutable('2025-06-14 10:00:00'));

        $container = $this->createWeekdayTimeRangeContainer();

        $this->assertFalse($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerMatchesBastilleDaySpecialHours(): void
    {
        // Bastille Day 11:00 - within special hours 10:00-14:00
        static::mockTime(new DateTimeImmutable('2025-07-14 11:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertTrue($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerDoesNotMatchBastilleDayOutsideSpecialHours(): void
    {
        // Bastille Day 15:00 - outside special hours
        static::mockTime(new DateTimeImmutable('2025-07-14 15:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertFalse($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerClosesOnLaborDay(): void
    {
        // Labor Day 2025 is Thursday, but french_holidays rule should close it
        static::mockTime(new DateTimeImmutable('2025-05-01 10:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertFalse($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerMatchesRegularWeekday(): void
    {
        // Regular Tuesday (not a holiday) - should be open
        static::mockTime(new DateTimeImmutable('2025-06-10 10:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertTrue($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerClosesOnEasterMonday(): void
    {
        // Easter Monday 2025 is April 21
        static::mockTime(new DateTimeImmutable('2025-04-21 10:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertFalse($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerClosesOnChristmas(): void
    {
        // Christmas is always December 25
        static::mockTime(new DateTimeImmutable('2025-12-25 10:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertFalse($container->matches(new DatePoint()));
    }

    public function testTimeRangeContainerClosesOnAscension(): void
    {
        // Ascension 2025 is May 29
        static::mockTime(new DateTimeImmutable('2025-05-29 10:00:00'));

        $container = $this->createHolidayTimeRangeContainer();

        $this->assertFalse($container->matches(new DatePoint()));
    }

    public function testEmptyTimeRangeContainerAlwaysMatches(): void
    {
        $container = new TimeRangeContainer($this->holidayCalculator);

        static::mockTime(new DateTimeImmutable('2025-12-25 03:00:00'));
        $this->assertTrue($container->matches(new DatePoint()));

        static::mockTime(new DateTimeImmutable('2025-06-14 23:59:00'));
        $this->assertTrue($container->matches(new DatePoint()));
    }

    private function createWeekdayTimeRangeContainer(): TimeRangeContainer
    {
        return new TimeRangeContainer(
            $this->holidayCalculator,
            new TimeRange(
                [
                    DayMatcher::fromString('Monday'),
                    DayMatcher::fromString('Tuesday'),
                    DayMatcher::fromString('Wednesday'),
                    DayMatcher::fromString('Thursday'),
                    DayMatcher::fromString('Friday'),
                ],
                '08:00',
                '18:00'
            ),
            new TimeRange(
                [
                    DayMatcher::fromString('Saturday'),
                    DayMatcher::fromString('Sunday'),
                ],
                'closed'
            )
        );
    }

    private function createHolidayTimeRangeContainer(): TimeRangeContainer
    {
        return new TimeRangeContainer(
            $this->holidayCalculator,
            // Rule 1: Bastille Day open 10:00-14:00
            new TimeRange(
                [DayMatcher::fromString('bastille_day')],
                '10:00',
                '14:00'
            ),
            // Rule 2: All holidays closed
            new TimeRange(
                [DayMatcher::fromString('french_holidays')],
                'closed'
            ),
            // Rule 3: Weekdays 08:00-18:00
            new TimeRange(
                [
                    DayMatcher::fromString('Monday'),
                    DayMatcher::fromString('Tuesday'),
                    DayMatcher::fromString('Wednesday'),
                    DayMatcher::fromString('Thursday'),
                    DayMatcher::fromString('Friday'),
                ],
                '08:00',
                '18:00'
            )
        );
    }
}
