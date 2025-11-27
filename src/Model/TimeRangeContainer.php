<?php

declare(strict_types=1);


namespace App\Model;

use App\Service\FrenchHolidayCalculator;
use Stringable;
use Symfony\Component\Clock\DatePoint;

class TimeRangeContainer implements Stringable
{
    /**
     * @var TimeRange[]
     */
    private array $ranges;

    public function __construct(
        private readonly FrenchHolidayCalculator $calculator,
        TimeRange ...$ranges,
    ) {
        $this->ranges = $ranges;
    }

    public function matches(DatePoint $date): bool
    {
        if (empty($this->ranges)) {
            return true;
        }

        foreach ($this->ranges as $range) {
            if ($range->appliesTo($date, $this->calculator)) {
                return $range->isOpen($date);
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode(', ', array_map(static fn(TimeRange $range) => (string)$range, $this->ranges));
    }
}

