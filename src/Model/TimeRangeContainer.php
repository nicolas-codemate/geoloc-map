<?php

declare(strict_types=1);


namespace App\Model;

use Stringable;

class TimeRangeContainer implements Stringable
{
    /**
     * @var TimeRange[]
     */
    private array $ranges;

    public function __construct(
        TimeRange ...$ranges,
    ) {
        if (!$ranges) {
            $this->ranges[] = new TimeRange(); // by default range is 24/7

            return;
        }

        $this->ranges = $ranges;
    }

    public function isCurrentTimeInRanges(): bool
    {
        return array_any($this->ranges, static fn(TimeRange $range) => $range->isCurrentTimeInRange());
    }

    public function __toString(): string
    {
        return implode(', ', array_map(static fn(TimeRange $range) => (string)$range, $this->ranges));
    }
}
