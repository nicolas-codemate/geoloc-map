<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\FrenchHoliday;
use App\Service\FrenchHolidayCalculator;
use PHPUnit\Framework\TestCase;

class FrenchHolidayTest extends TestCase
{
    public function testAllHolidaysHaveUniqueKeywords(): void
    {
        $keywords = array_map(fn($h) => $h->getKeyword(), FrenchHoliday::cases());
        $this->assertCount(11, $keywords);
        $this->assertCount(11, array_unique($keywords), 'All holiday keywords must be unique');
    }

    public function testFixedHolidaysAreMarkedCorrectly(): void
    {
        $this->assertTrue(FrenchHoliday::NewYear->isFixedDate());
        $this->assertTrue(FrenchHoliday::LaborDay->isFixedDate());
        $this->assertTrue(FrenchHoliday::BastilleDay->isFixedDate());
        
        $this->assertFalse(FrenchHoliday::EasterMonday->isFixedDate());
        $this->assertFalse(FrenchHoliday::Ascension->isFixedDate());
        $this->assertFalse(FrenchHoliday::WhitMonday->isFixedDate());
    }

    public function testGetFixedMonthDayReturnsCorrectFormat(): void
    {
        $this->assertEquals('01-01', FrenchHoliday::NewYear->getFixedMonthDay());
        $this->assertEquals('05-01', FrenchHoliday::LaborDay->getFixedMonthDay());
        $this->assertEquals('07-14', FrenchHoliday::BastilleDay->getFixedMonthDay());
        $this->assertEquals('12-25', FrenchHoliday::Christmas->getFixedMonthDay());
        
        $this->assertNull(FrenchHoliday::EasterMonday->getFixedMonthDay());
    }

    public function testFromKeywordReturnsCorrectHoliday(): void
    {
        $this->assertSame(FrenchHoliday::NewYear, FrenchHoliday::fromKeyword('new_year'));
        $this->assertSame(FrenchHoliday::EasterMonday, FrenchHoliday::fromKeyword('easter_monday'));
        $this->assertSame(FrenchHoliday::BastilleDay, FrenchHoliday::fromKeyword('bastille_day'));
    }

    public function testFromKeywordIsCaseInsensitive(): void
    {
        $this->assertSame(FrenchHoliday::LaborDay, FrenchHoliday::fromKeyword('LABOR_DAY'));
        $this->assertSame(FrenchHoliday::LaborDay, FrenchHoliday::fromKeyword('Labor_Day'));
    }

    public function testFromKeywordReturnsNullForInvalid(): void
    {
        $this->assertNull(FrenchHoliday::fromKeyword('invalid'));
        $this->assertNull(FrenchHoliday::fromKeyword(''));
        $this->assertNull(FrenchHoliday::fromKeyword('saint_patrick'));
    }

    public function testGetNameReturnsReadableName(): void
    {
        $this->assertEquals("New Year's Day", FrenchHoliday::NewYear->getName());
        $this->assertEquals('Easter Monday', FrenchHoliday::EasterMonday->getName());
        $this->assertEquals('Labor Day', FrenchHoliday::LaborDay->getName());
        $this->assertEquals('Bastille Day', FrenchHoliday::BastilleDay->getName());
    }

    public function testAllReturnsExactly11Holidays(): void
    {
        $holidays = FrenchHoliday::all();
        $this->assertCount(11, $holidays);
        $this->assertContainsOnlyInstancesOf(FrenchHoliday::class, $holidays);
    }
}
