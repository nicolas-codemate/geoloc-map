<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\FrenchHoliday;
use App\Service\FrenchHolidayCalculator;
use PHPUnit\Framework\TestCase;

class FrenchHolidayCalculatorTest extends TestCase
{
    private FrenchHolidayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new FrenchHolidayCalculator();
    }

    public function testNewYear2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::NewYear, 2025);
        $this->assertEquals('2025-01-01', $date->format('Y-m-d'));
    }

    public function testLaborDay2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::LaborDay, 2025);
        $this->assertEquals('2025-05-01', $date->format('Y-m-d'));
    }

    public function testVictoryDay2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::VictoryDay, 2025);
        $this->assertEquals('2025-05-08', $date->format('Y-m-d'));
    }

    public function testBastilleDay2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::BastilleDay, 2025);
        $this->assertEquals('2025-07-14', $date->format('Y-m-d'));
    }

    public function testAssumption2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::Assumption, 2025);
        $this->assertEquals('2025-08-15', $date->format('Y-m-d'));
    }

    public function testAllSaints2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::AllSaints, 2025);
        $this->assertEquals('2025-11-01', $date->format('Y-m-d'));
    }

    public function testArmistice2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::Armistice, 2025);
        $this->assertEquals('2025-11-11', $date->format('Y-m-d'));
    }

    public function testChristmas2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::Christmas, 2025);
        $this->assertEquals('2025-12-25', $date->format('Y-m-d'));
    }

    public function testEasterMonday2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::EasterMonday, 2025);
        $this->assertEquals('2025-04-21', $date->format('Y-m-d'));
        $this->assertEquals('Monday', $date->format('l'));
    }

    public function testAscension2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::Ascension, 2025);
        $this->assertEquals('2025-05-29', $date->format('Y-m-d'));
        $this->assertEquals('Thursday', $date->format('l'));
    }

    public function testWhitMonday2025(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::WhitMonday, 2025);
        $this->assertEquals('2025-06-09', $date->format('Y-m-d'));
        $this->assertEquals('Monday', $date->format('l'));
    }

    public function testEasterMonday2026(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::EasterMonday, 2026);
        $this->assertEquals('2026-04-06', $date->format('Y-m-d'));
    }

    public function testEasterMonday2027(): void
    {
        $date = $this->calculator->getHolidayDate(FrenchHoliday::EasterMonday, 2027);
        $this->assertEquals('2027-03-29', $date->format('Y-m-d'));
    }

    public function testIsHolidayDetectsHolidays(): void
    {
        $newYear = new \DateTimeImmutable('2025-01-01');
        $laborDay = new \DateTimeImmutable('2025-05-01');
        $regularDay = new \DateTimeImmutable('2025-06-15');

        $this->assertSame(FrenchHoliday::NewYear, $this->calculator->isHoliday($newYear));
        $this->assertSame(FrenchHoliday::LaborDay, $this->calculator->isHoliday($laborDay));
        $this->assertNull($this->calculator->isHoliday($regularDay));
    }

    public function testCachingWorksProperly(): void
    {
        // First call calculates
        $date1 = $this->calculator->getHolidayDate(FrenchHoliday::EasterMonday, 2025);
        // Second call should use cache
        $date2 = $this->calculator->getHolidayDate(FrenchHoliday::EasterMonday, 2025);
        
        $this->assertSame($date1, $date2);
    }
}
