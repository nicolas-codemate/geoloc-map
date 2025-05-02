<?php

declare(strict_types=1);


namespace App\Model;

use Symfony\Component\Clock\DatePoint;

class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?DatePoint $dateTime = null,
    ) {
    }


}
