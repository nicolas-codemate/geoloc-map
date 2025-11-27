<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Enum\FrenchHoliday;
use App\Exception\InvalidDayMatcherException;
use App\Model\DayMatcher;
use App\Service\FrenchHolidayCalculator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class DayMatcherTest extends TestCase
{
    private FrenchHolidayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new FrenchHolidayCalculator();
    }

    public function testMatchesDayOfWeek(): void
    {
        $matcher = DayMatcher::fromString('Monday');
        $monday = new DatePoint('2025-05-05'); // Monday
        $tuesday = new DatePoint('2025-05-06'); // Tuesday

        $this->assertTrue($matcher->matches($monday, $this->calculator));
        $this->assertFalse($matcher->matches($tuesday, $this->calculator));
    }

    public function testMatchesDayOfWeekCaseInsensitive(): void
    {
        $matcher = DayMatcher::fromString('monday');
        $monday = new DatePoint('2025-05-05');

        $this->assertTrue($matcher->matches($monday, $this->calculator));
    }

    public function testMatchesFixedMonthDay(): void
    {
        $matcher = DayMatcher::fromString('12-25');
        $christmas2025 = new DatePoint('2025-12-25');
        $christmas2026 = new DatePoint('2026-12-25');
        $notChristmas = new DatePoint('2025-12-24');

        $this->assertTrue($matcher->matches($christmas2025, $this->calculator));
        $this->assertTrue($matcher->matches($christmas2026, $this->calculator));
        $this->assertFalse($matcher->matches($notChristmas, $this->calculator));
    }

    public function testMatchesFullDate(): void
    {
        $matcher = DayMatcher::fromString('2025-12-24');
        $date2025 = new DatePoint('2025-12-24');
        $date2026 = new DatePoint('2026-12-24');

        $this->assertTrue($matcher->matches($date2025, $this->calculator));
        $this->assertFalse($matcher->matches($date2026, $this->calculator));
    }

    public function testMatchesHolidayKeyword(): void
    {
        $matcher = DayMatcher::fromString('labor_day');
        $laborDay2025 = new DatePoint('2025-05-01');
        $notLaborDay = new DatePoint('2025-05-02');

        $this->assertTrue($matcher->matches($laborDay2025, $this->calculator));
        $this->assertFalse($matcher->matches($notLaborDay, $this->calculator));
    }

    public function testMatchesEasterMonday(): void
    {
        $matcher = DayMatcher::fromString('easter_monday');
        $easterMonday2025 = new DatePoint('2025-04-21');
        $notEasterMonday = new DatePoint('2025-04-20');

        $this->assertTrue($matcher->matches($easterMonday2025, $this->calculator));
        $this->assertFalse($matcher->matches($notEasterMonday, $this->calculator));
    }

    public function testMatchesAllFrenchHolidays(): void
    {
        $matcher = DayMatcher::fromString('french_holidays');
        
        // Test some holidays
        $newYear = new DatePoint('2025-01-01');
        $laborDay = new DatePoint('2025-05-01');
        $bastilleDay = new DatePoint('2025-07-14');
        $christmas = new DatePoint('2025-12-25');
        $regularDay = new DatePoint('2025-06-15');

        $this->assertTrue($matcher->matches($newYear, $this->calculator));
        $this->assertTrue($matcher->matches($laborDay, $this->calculator));
        $this->assertTrue($matcher->matches($bastilleDay, $this->calculator));
        $this->assertTrue($matcher->matches($christmas, $this->calculator));
        $this->assertFalse($matcher->matches($regularDay, $this->calculator));
    }

    public function testInvalidDayFormatThrowsException(): void
    {
        $this->expectException(InvalidDayMatcherException::class);
        DayMatcher::fromString('invalid_format');
    }

    public function testToString(): void
    {
        $mondayMatcher = DayMatcher::fromString('Monday');
        $this->assertEquals('Monday', (string) $mondayMatcher);

        $dateMatcher = DayMatcher::fromString('2025-12-25');
        $this->assertEquals('2025-12-25', (string) $dateMatcher);

        $holidayMatcher = DayMatcher::fromString('labor_day');
        $this->assertEquals('labor_day', (string) $holidayMatcher);

        $allHolidaysMatcher = DayMatcher::fromString('french_holidays');
        $this->assertEquals('french_holidays', (string) $allHolidaysMatcher);
    }
}
