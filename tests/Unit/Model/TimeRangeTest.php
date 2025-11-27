<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\DayMatcher;
use App\Model\TimeRange;
use App\Service\FrenchHolidayCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class TimeRangeTest extends TestCase
{
    private FrenchHolidayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new FrenchHolidayCalculator();
    }

    public function testNormalTimeRangeWithinHours(): void
    {
        $dayMatchers = [DayMatcher::fromString('Monday')];
        $range = new TimeRange($dayMatchers, '09:00', '17:00');

        $mondayMorning = new DatePoint('2025-05-05 10:00:00'); // Monday 10:00
        $mondayEvening = new DatePoint('2025-05-05 18:00:00'); // Monday 18:00

        $this->assertTrue($range->appliesTo($mondayMorning, $this->calculator));
        $this->assertTrue($range->isOpen($mondayMorning));
        
        $this->assertTrue($range->appliesTo($mondayEvening, $this->calculator));
        $this->assertFalse($range->isOpen($mondayEvening));
    }

    public function testClosedKeyword(): void
    {
        $dayMatchers = [DayMatcher::fromString('labor_day')];
        $range = new TimeRange($dayMatchers, 'closed');

        $laborDay = new DatePoint('2025-05-01 10:00:00');
        
        $this->assertTrue($range->appliesTo($laborDay, $this->calculator));
        $this->assertFalse($range->isOpen($laborDay));
    }

    public function testOpenKeyword(): void
    {
        $dayMatchers = [DayMatcher::fromString('Saturday')];
        $range = new TimeRange($dayMatchers, 'open');

        $saturdayNight = new DatePoint('2025-05-03 23:00:00'); // Saturday 23:00
        
        $this->assertTrue($range->appliesTo($saturdayNight, $this->calculator));
        $this->assertTrue($range->isOpen($saturdayNight));
    }

    public function testDoesNotApplyToDifferentDay(): void
    {
        $dayMatchers = [DayMatcher::fromString('Monday')];
        $range = new TimeRange($dayMatchers, '09:00', '17:00');

        $tuesday = new DatePoint('2025-05-06 10:00:00'); // Tuesday
        
        $this->assertFalse($range->appliesTo($tuesday, $this->calculator));
    }

    public function testMultipleDayMatchers(): void
    {
        $dayMatchers = [
            DayMatcher::fromString('Monday'),
            DayMatcher::fromString('Tuesday'),
            DayMatcher::fromString('Wednesday'),
        ];
        $range = new TimeRange($dayMatchers, '09:00', '17:00');

        $monday = new DatePoint('2025-05-05 10:00:00');
        $tuesday = new DatePoint('2025-05-06 10:00:00');
        $thursday = new DatePoint('2025-05-08 10:00:00');
        
        $this->assertTrue($range->appliesTo($monday, $this->calculator));
        $this->assertTrue($range->appliesTo($tuesday, $this->calculator));
        $this->assertFalse($range->appliesTo($thursday, $this->calculator));
    }

    public function testBoundaryTimes(): void
    {
        $dayMatchers = [DayMatcher::fromString('Monday')];
        $range = new TimeRange($dayMatchers, '09:00', '17:00');

        $exactStart = new DatePoint('2025-05-05 09:00:00');
        $exactEnd = new DatePoint('2025-05-05 17:00:00');
        $beforeStart = new DatePoint('2025-05-05 08:59:00');
        $afterEnd = new DatePoint('2025-05-05 17:01:00');
        
        $this->assertTrue($range->isOpen($exactStart));
        $this->assertTrue($range->isOpen($exactEnd));
        $this->assertFalse($range->isOpen($beforeStart));
        $this->assertFalse($range->isOpen($afterEnd));
    }
}
