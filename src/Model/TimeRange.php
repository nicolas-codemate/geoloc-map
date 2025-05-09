<?php

declare(strict_types=1);


namespace App\Model;

use Stringable;
use Symfony\Component\Clock\DatePoint;

use function in_array;

class TimeRange implements Stringable
{

    /**
     * Default value is 24/7
     */
    public function __construct(
        private ?array $days = null,
        private readonly string $startTime = '00:00',
        private readonly string $endTime = '23:59',
    ) {
        $this->days = $days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    }

    public function isCurrentTimeInRange(): bool
    {
        $now = new DatePoint();

        $currentDay = $now->format('l'); // 'l' gives the full-day name
        $currentTime = $now->format('H:i');

        if (false === in_array($currentDay, $this->days, true)) {
            return false;
        }

        return ($currentTime >= $this->startTime && $currentTime <= $this->endTime);
    }

    public function __toString(): string
    {
        return sprintf(
            'TimeRange: %s, %s - %s',
            implode(', ', $this->days),
            $this->startTime,
            $this->endTime
        );
    }
}
