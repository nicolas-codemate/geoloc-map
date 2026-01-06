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

    private const string DEFAULT_CUSTOM_MESSAGE = 'Aucune donnée de géolocalisation';

    public function __construct(
        public string $mapName,
        public Coordinates $defaultCoordinates,
        public int $defaultZoomLevel,
        public int $refreshInterval,
        public TimeRangeContainer $timeRangeContainer,
        public string $customMessage = self::DEFAULT_CUSTOM_MESSAGE,
        GeolocatableObjectInterface ...$geolocatableObjects,
    ) {
        $this->geolocatableObjects = $geolocatableObjects;
    }
}
