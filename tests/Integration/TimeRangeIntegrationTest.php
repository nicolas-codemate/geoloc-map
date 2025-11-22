<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Model\DayMatcher;
use App\Model\TimeRange;
use App\Model\TimeRangeContainer;
use App\Service\FrenchHolidayCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class TimeRangeIntegrationTest extends TestCase
{
    private FrenchHolidayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new FrenchHolidayCalculator();
    }

    public function testBusinessWithHolidayExceptionsScenario(): void
    {
        // Scenario: Business open weekdays 8-18, but:
        // - Bastille Day: open only 10-14
        // - All other holidays: closed
        // - Weekends: closed

        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [DayMatcher::fromString('bastille_day')],
                '10:00',
                '14:00'
            ),
            new TimeRange(
                [DayMatcher::fromString('french_holidays')],
                'closed'
            ),
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

        // Test cases
        $this->assertTrue($container->matches(new DatePoint('2025-07-14 11:00')), 'Bastille Day 11:00 should be open');
        $this->assertFalse($container->matches(new DatePoint('2025-07-14 15:00')), 'Bastille Day 15:00 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-05-01 10:00')), 'Labor Day should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-12-25 10:00')), 'Christmas should be closed');
        $this->assertTrue($container->matches(new DatePoint('2025-06-10 10:00')), 'Regular Tuesday should be open');
        $this->assertFalse($container->matches(new DatePoint('2025-06-10 19:00')), 'Tuesday 19:00 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-06-14 10:00')), 'Saturday should be closed');
    }

    public function testSimpleWeekdayScheduleScenario(): void
    {
        // Scenario: Simple business hours - weekdays 9-17, weekends closed

        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [DayMatcher::fromString('french_holidays')],
                'closed'
            ),
            new TimeRange(
                [DayMatcher::fromString('Sunday')],
                'closed'
            ),
            new TimeRange(
                [DayMatcher::fromString('Monday')],
                'closed'
            ),
            new TimeRange(
                [
                    DayMatcher::fromString('Tuesday'),
                    DayMatcher::fromString('Wednesday'),
                    DayMatcher::fromString('Thursday'),
                    DayMatcher::fromString('Friday'),
                    DayMatcher::fromString('Saturday'),
                ],
                '09:00',
                '17:00'
            )
        );

        $this->assertFalse($container->matches(new DatePoint('2025-01-01 13:00')), 'New Year should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-06-08 13:00')), 'Sunday should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-06-09 13:00')), 'Monday should be closed');
        $this->assertTrue($container->matches(new DatePoint('2025-06-10 13:00')), 'Tuesday 13:00 should be open');
        $this->assertFalse($container->matches(new DatePoint('2025-06-10 20:00')), 'Tuesday 20:00 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-06-10 07:00')), 'Tuesday 07:00 should be closed');
    }

    public function testChristmasEveSpecialHoursScenario(): void
    {
        // Scenario: Business with special hours on Christmas Eve
        
        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [DayMatcher::fromString('2025-12-24')],
                '08:00',
                '12:00'
            ),
            new TimeRange(
                [DayMatcher::fromString('french_holidays')],
                'closed'
            ),
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

        $this->assertTrue($container->matches(new DatePoint('2025-12-24 10:00')), 'Christmas Eve morning should be open');
        $this->assertFalse($container->matches(new DatePoint('2025-12-24 14:00')), 'Christmas Eve afternoon should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-12-25 10:00')), 'Christmas should be closed');
        $this->assertTrue($container->matches(new DatePoint('2025-12-23 10:00')), 'Day before Christmas Eve should be normal hours');
    }

    public function testRecurringAnnualEventsScenario(): void
    {
        // Scenario: Business closed every May 1st and December 25th (using MM-DD format)

        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [
                    DayMatcher::fromString('05-01'),
                    DayMatcher::fromString('12-25'),
                ],
                'closed'
            ),
            new TimeRange(
                [
                    DayMatcher::fromString('Monday'),
                    DayMatcher::fromString('Tuesday'),
                    DayMatcher::fromString('Wednesday'),
                    DayMatcher::fromString('Thursday'),
                    DayMatcher::fromString('Friday'),
                ],
                '09:00',
                '17:00'
            )
        );

        $this->assertFalse($container->matches(new DatePoint('2025-05-01 10:00')), 'May 1st 2025 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2026-05-01 10:00')), 'May 1st 2026 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-12-25 10:00')), 'Dec 25th 2025 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2026-12-25 10:00')), 'Dec 25th 2026 should be closed');
        $this->assertTrue($container->matches(new DatePoint('2025-05-02 10:00')), 'May 2nd should be open');
    }

    public function testAlwaysOpenScenario(): void
    {
        // Scenario: 24/7 service

        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [
                    DayMatcher::fromString('Monday'),
                    DayMatcher::fromString('Tuesday'),
                    DayMatcher::fromString('Wednesday'),
                    DayMatcher::fromString('Thursday'),
                    DayMatcher::fromString('Friday'),
                    DayMatcher::fromString('Saturday'),
                    DayMatcher::fromString('Sunday'),
                ],
                'open'
            )
        );

        $this->assertTrue($container->matches(new DatePoint('2025-01-01 00:00')), 'Midnight New Year should be open');
        $this->assertTrue($container->matches(new DatePoint('2025-07-14 23:59')), 'Late night Bastille Day should be open');
        $this->assertTrue($container->matches(new DatePoint('2025-06-15 03:00')), 'Early morning regular day should be open');
    }

    public function testEasterBasedHolidaysScenario(): void
    {
        // Scenario: Closed on all Easter-based holidays

        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [
                    DayMatcher::fromString('easter_monday'),
                    DayMatcher::fromString('ascension'),
                    DayMatcher::fromString('whit_monday'),
                ],
                'closed'
            ),
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

        $this->assertFalse($container->matches(new DatePoint('2025-04-21 10:00')), 'Easter Monday 2025 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-05-29 10:00')), 'Ascension 2025 should be closed');
        $this->assertFalse($container->matches(new DatePoint('2025-06-09 10:00')), 'Whit Monday 2025 should be closed');
        $this->assertTrue($container->matches(new DatePoint('2025-04-22 10:00')), 'Day after Easter Monday should be open');
    }

    public function testPriorityOrderMatters(): void
    {
        // Scenario: Testing that order of rules matters
        
        // Configuration A: Specific holiday BEFORE general holidays
        $containerA = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [DayMatcher::fromString('bastille_day')],
                '10:00',
                '14:00'
            ),
            new TimeRange(
                [DayMatcher::fromString('french_holidays')],
                'closed'
            )
        );

        // Configuration B: General holidays BEFORE specific holiday (wrong order)
        $containerB = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [DayMatcher::fromString('french_holidays')],
                'closed'
            ),
            new TimeRange(
                [DayMatcher::fromString('bastille_day')],
                '10:00',
                '14:00'
            )
        );

        $bastilleDay = new DatePoint('2025-07-14 11:00');

        // Container A: Bastille Day rule matches first -> open
        $this->assertTrue($containerA->matches($bastilleDay), 'Config A: Bastille Day should be open (specific rule first)');

        // Container B: french_holidays matches first -> closed (Bastille Day rule never reached)
        $this->assertFalse($containerB->matches($bastilleDay), 'Config B: Bastille Day should be closed (general rule first)');
    }

    public function testMixedDateFormatsScenario(): void
    {
        // Scenario: Using different date formats together

        $container = new TimeRangeContainer(
            $this->calculator,
            new TimeRange(
                [DayMatcher::fromString('2025-12-31')], // Specific year
                '08:00',
                '12:00'
            ),
            new TimeRange(
                [DayMatcher::fromString('12-31')], // Every year
                'closed'
            ),
            new TimeRange(
                [DayMatcher::fromString('labor_day')], // Holiday keyword
                'closed'
            ),
            new TimeRange(
                [DayMatcher::fromString('Monday')], // Day of week
                '09:00',
                '17:00'
            )
        );

        $this->assertTrue($container->matches(new DatePoint('2025-12-31 10:00')), '2025 NYE morning should be open');
        $this->assertFalse($container->matches(new DatePoint('2026-12-31 10:00')), '2026 NYE should be closed (catches general rule)');
        $this->assertFalse($container->matches(new DatePoint('2025-05-01 10:00')), 'Labor Day should be closed');
        $this->assertTrue($container->matches(new DatePoint('2025-06-02 10:00')), 'Regular Monday should be open');
    }
}
