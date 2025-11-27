<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\FrenchHoliday;
use DateTimeImmutable;

class FrenchHolidayCalculator
{
    /**
     * @var array<string, DateTimeImmutable>
     */
    private array $cache = [];

    public function getHolidayDate(FrenchHoliday $holiday, int $year): DateTimeImmutable
    {
        $cacheKey = "{$holiday->value}_{$year}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $date = match ($holiday) {
            FrenchHoliday::NewYear => new DateTimeImmutable("{$year}-01-01"),
            FrenchHoliday::LaborDay => new DateTimeImmutable("{$year}-05-01"),
            FrenchHoliday::VictoryDay => new DateTimeImmutable("{$year}-05-08"),
            FrenchHoliday::BastilleDay => new DateTimeImmutable("{$year}-07-14"),
            FrenchHoliday::Assumption => new DateTimeImmutable("{$year}-08-15"),
            FrenchHoliday::AllSaints => new DateTimeImmutable("{$year}-11-01"),
            FrenchHoliday::Armistice => new DateTimeImmutable("{$year}-11-11"),
            FrenchHoliday::Christmas => new DateTimeImmutable("{$year}-12-25"),
            FrenchHoliday::EasterMonday => $this->calculateEasterMonday($year),
            FrenchHoliday::Ascension => $this->calculateAscension($year),
            FrenchHoliday::WhitMonday => $this->calculateWhitMonday($year),
        };

        $this->cache[$cacheKey] = $date;

        return $date;
    }

    public function isHoliday(DateTimeImmutable $date): ?FrenchHoliday
    {
        $year = (int) $date->format('Y');

        foreach (FrenchHoliday::cases() as $holiday) {
            $holidayDate = $this->getHolidayDate($holiday, $year);
            if ($holidayDate->format('Y-m-d') === $date->format('Y-m-d')) {
                return $holiday;
            }
        }

        return null;
    }

    private function calculateEasterSunday(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $day));
    }

    private function calculateEasterMonday(int $year): DateTimeImmutable
    {
        return $this->calculateEasterSunday($year)->modify('+1 day');
    }

    private function calculateAscension(int $year): DateTimeImmutable
    {
        return $this->calculateEasterSunday($year)->modify('+39 days');
    }

    private function calculateWhitMonday(int $year): DateTimeImmutable
    {
        return $this->calculateEasterSunday($year)->modify('+50 days');
    }
}
