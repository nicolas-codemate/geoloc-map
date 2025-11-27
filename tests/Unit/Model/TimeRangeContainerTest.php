<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\DayMatcher;
use App\Model\TimeRange;
use App\Model\TimeRangeContainer;
use App\Service\FrenchHolidayCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class TimeRangeContainerTest extends TestCase
{
    private FrenchHolidayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new FrenchHolidayCalculator();
    }

    public function testEmptyContainerReturnsTrue(): void
    {
        $container = new TimeRangeContainer($this->calculator);
        $anyDate = new DatePoint('2025-05-05 10:00:00');
        
        $this->assertTrue($container->matches($anyDate));
    }

    public function testFirstMatchWinsPriority(): void
    {
        // Rule 1: Bastille Day is open 10:00-14:00
        $bastilleDayMatchers = [DayMatcher::fromString('bastille_day')];
        $bastilleDayRange = new TimeRange($bastilleDayMatchers, '10:00', '14:00');

        // Rule 2: All holidays are closed
        $allHolidaysMatchers = [DayMatcher::fromString('french_holidays')];
        $allHolidaysClosed = new TimeRange($allHolidaysMatchers, 'closed');

        // Bastille Day should match first rule (open 10:00-14:00)
        $container = new TimeRangeContainer(
            $this->calculator,
            $bastilleDayRange,
            $allHolidaysClosed
        );

        $bastilleDayMorning = new DatePoint('2025-07-14 11:00:00');
        $bastilleDayEvening = new DatePoint('2025-07-14 15:00:00');
        
        $this->assertTrue($container->matches($bastilleDayMorning)); // Within 10-14
        $this->assertFalse($container->matches($bastilleDayEvening)); // Outside 10-14, but rule matched
    }

    public function testSecondRuleMatchesWhenFirstDoesNot(): void
    {
        $bastilleDayMatchers = [DayMatcher::fromString('bastille_day')];
        $bastilleDayRange = new TimeRange($bastilleDayMatchers, '10:00', '14:00');

        $allHolidaysMatchers = [DayMatcher::fromString('french_holidays')];
        $allHolidaysClosed = new TimeRange($allHolidaysMatchers, 'closed');

        $container = new TimeRangeContainer(
            $this->calculator,
            $bastilleDayRange,
            $allHolidaysClosed
        );

        // Labor Day (not Bastille Day) should match second rule (closed)
        $laborDay = new DatePoint('2025-05-01 11:00:00');
        
        $this->assertFalse($container->matches($laborDay));
    }

    public function testComplexScenario(): void
    {
        // Rule 1: Bastille Day open 10:00-14:00
        $bastilleDayRange = new TimeRange(
            [DayMatcher::fromString('bastille_day')],
            '10:00',
            '14:00'
        );

        // Rule 2: All holidays closed
        $allHolidaysClosed = new TimeRange(
            [DayMatcher::fromString('french_holidays')],
            'closed'
        );

        // Rule 3: Weekdays 08:00-18:00
        $weekdaysRange = new TimeRange(
            [
                DayMatcher::fromString('Monday'),
                DayMatcher::fromString('Tuesday'),
                DayMatcher::fromString('Wednesday'),
                DayMatcher::fromString('Thursday'),
                DayMatcher::fromString('Friday'),
            ],
            '08:00',
            '18:00'
        );

        // Rule 4: Weekends closed
        $weekendClosed = new TimeRange(
            [
                DayMatcher::fromString('Saturday'),
                DayMatcher::fromString('Sunday'),
            ],
            'closed'
        );

        $container = new TimeRangeContainer(
            $this->calculator,
            $bastilleDayRange,
            $allHolidaysClosed,
            $weekdaysRange,
            $weekendClosed
        );

        // Bastille Day (Monday) 11:00 -> Rule 1 matches -> open
        $bastilleDay = new DatePoint('2025-07-14 11:00:00');
        $this->assertTrue($container->matches($bastilleDay));

        // Labor Day (Thursday) 10:00 -> Rule 2 matches -> closed
        $laborDay = new DatePoint('2025-05-01 10:00:00');
        $this->assertFalse($container->matches($laborDay));

        // Regular Tuesday 10:00 -> Rule 3 matches -> open
        $tuesday = new DatePoint('2025-05-06 10:00:00');
        $this->assertTrue($container->matches($tuesday));

        // Regular Tuesday 19:00 -> Rule 3 matches but outside hours -> closed
        $tuesdayEvening = new DatePoint('2025-05-06 19:00:00');
        $this->assertFalse($container->matches($tuesdayEvening));

        // Saturday 10:00 -> Rule 4 matches -> closed
        $saturday = new DatePoint('2025-05-03 10:00:00');
        $this->assertFalse($container->matches($saturday));
    }

    public function testNoMatchReturnsClosedByDefault(): void
    {
        $weekdaysRange = new TimeRange(
            [DayMatcher::fromString('Monday')],
            '09:00',
            '17:00'
        );

        $container = new TimeRangeContainer($this->calculator, $weekdaysRange);

        // Tuesday does not match Monday rule -> closed
        $tuesday = new DatePoint('2025-05-06 10:00:00');
        $this->assertFalse($container->matches($tuesday));
    }

    public function testOpenKeywordReturnsTrue(): void
    {
        $weekendsOpen = new TimeRange(
            [
                DayMatcher::fromString('Saturday'),
                DayMatcher::fromString('Sunday'),
            ],
            'open'
        );

        $container = new TimeRangeContainer($this->calculator, $weekendsOpen);

        $saturdayMidnight = new DatePoint('2025-05-03 00:00:00');
        $this->assertTrue($container->matches($saturdayMidnight));
    }
}
