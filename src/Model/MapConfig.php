<?php

declare(strict_types=1);


namespace App\Model;

class MapConfig implements MapConfigInterface
{

    public array $geolocatableObjects = [] {
        get {
            return $this->geolocatableObjects;
        }
    }

    public function __construct(
        public string $mapName,
        public Coordinates $defaultCoordinates,
        public int $defaultZoomLevel,
        public int $refreshInterval,
        public TimeRangeContainer $timeRangeContainer,
        GeolocatableObjectInterface ...$geolocatableObjects,
    ) {
        $this->geolocatableObjects = $geolocatableObjects;
    }
}
