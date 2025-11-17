<?php

declare(strict_types=1);


namespace App\Model;

use App\Service\FrenchHolidayCalculator;
use Stringable;
use Symfony\Component\Clock\DatePoint;

class TimeRange implements Stringable
{
    /**
     * @param DayMatcher[] $dayMatchers
     */
    public function __construct(
        private readonly array $dayMatchers,
        private readonly string $startTime = '00:00',
        private readonly string $endTime = '23:59',
    ) {
    }

    public function appliesTo(DatePoint $date, FrenchHolidayCalculator $calculator): bool
    {
        foreach ($this->dayMatchers as $dayMatcher) {
            if ($dayMatcher->matches($date, $calculator)) {
                return true;
            }
        }

        return false;
    }

    public function isOpen(DatePoint $date): bool
    {
        if ($this->startTime === 'closed') {
            return false;
        }

        if ($this->startTime === 'open') {
            return true;
        }

        $currentTime = $date->format('H:i');
        return ($currentTime >= $this->startTime && $currentTime <= $this->endTime);
    }

    public function __toString(): string
    {
        $days = array_map(fn(DayMatcher $dm) => (string) $dm, $this->dayMatchers);

        return sprintf(
            'TimeRange: %s, %s - %s',
            implode(', ', $days),
            $this->startTime,
            $this->endTime
        );
    }
}
