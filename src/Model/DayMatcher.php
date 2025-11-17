<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\FrenchHoliday;
use App\Exception\InvalidDayMatcherException;
use App\Service\FrenchHolidayCalculator;
use Symfony\Component\Clock\DatePoint;
use Stringable;

readonly class DayMatcher implements Stringable
{
    private function __construct(
        private DayMatcherType $type,
        private ?string $dayOfWeek = null,
        private ?string $fixedMonthDay = null,
        private ?string $fullDate = null,
        private ?FrenchHoliday $holiday = null,
    ) {
    }

    public static function fromString(string $day, FrenchHolidayCalculator $calculator): self
    {
        $day = trim($day);

        if (strtolower($day) === 'french_holidays') {
            return new self(DayMatcherType::AllFrenchHolidays);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            return new self(DayMatcherType::FullDate, fullDate: $day);
        }

        if (preg_match('/^\d{2}-\d{2}$/', $day)) {
            return new self(DayMatcherType::FixedMonthDay, fixedMonthDay: $day);
        }

        $holiday = FrenchHoliday::fromKeyword($day);
        if ($holiday !== null) {
            return new self(DayMatcherType::Holiday, holiday: $holiday);
        }

        $validDaysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayCapitalized = ucfirst(strtolower($day));
        if (in_array($dayCapitalized, $validDaysOfWeek, true)) {
            return new self(DayMatcherType::DayOfWeek, dayOfWeek: $dayCapitalized);
        }

        throw new InvalidDayMatcherException(
            "Invalid day format: '{$day}'. Expected formats: day name (Monday), MM-DD (05-01), YYYY-MM-DD (2025-12-25), holiday keyword (labor_day), or 'french_holidays' for all French public holidays"
        );
    }

    public function matches(DatePoint $date, FrenchHolidayCalculator $calculator): bool
    {
        return match ($this->type) {
            DayMatcherType::DayOfWeek => $date->format('l') === $this->dayOfWeek,
            DayMatcherType::FixedMonthDay => $date->format('m-d') === $this->fixedMonthDay,
            DayMatcherType::FullDate => $date->format('Y-m-d') === $this->fullDate,
            DayMatcherType::Holiday => $this->matchesHoliday($date, $calculator),
            DayMatcherType::AllFrenchHolidays => $this->matchesAnyHoliday($date, $calculator),
        };
    }

    private function matchesAnyHoliday(DatePoint $date, FrenchHolidayCalculator $calculator): bool
    {
        return $calculator->isHoliday($date) !== null;
    }

    private function matchesHoliday(DatePoint $date, FrenchHolidayCalculator $calculator): bool
    {
        if ($this->holiday === null) {
            return false;
        }

        $year = (int) $date->format('Y');
        $holidayDate = $calculator->getHolidayDate($this->holiday, $year);

        return $holidayDate->format('Y-m-d') === $date->format('Y-m-d');
    }

    public function __toString(): string
    {
        return match ($this->type) {
            DayMatcherType::DayOfWeek => $this->dayOfWeek ?? '',
            DayMatcherType::FixedMonthDay => $this->fixedMonthDay ?? '',
            DayMatcherType::FullDate => $this->fullDate ?? '',
            DayMatcherType::Holiday => $this->holiday?->getKeyword() ?? '',
            DayMatcherType::AllFrenchHolidays => 'french_holidays',
        };
    }
}
