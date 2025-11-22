<?php

declare(strict_types=1);


namespace App\Model;


interface MapConfigInterface
{
    public string $mapName {
        get;
    }

    public Coordinates $defaultCoordinates {
        get;
    }

    public int $defaultZoomLevel {
        get;
    }

    public int $refreshInterval {
        get;
    }

    /**
     * @var GeolocatableObjectInterface[]
     */
    public array $geolocatableObjects {
        get;
    }

    public TimeRangeContainer $timeRangeContainer {
        get;
    }
}
