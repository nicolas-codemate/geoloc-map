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
        public string $name,
        public Coordinates $defaultCoordinates,
        public int $defaultZoomLevel,
        GeolocatableObjectInterface ...$geolocatableObjects,
    ) {
        $this->geolocatableObjects = $geolocatableObjects;
    }
}
