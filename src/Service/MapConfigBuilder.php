<?php

declare(strict_types=1);


namespace App\Service;

use App\Exception\MapConfigNotFoundException;
use App\Model\Coordinates;
use App\Model\GeolocatableObject;
use App\Model\MapConfig;
use App\Model\MapConfigInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MapConfigBuilder
{
    public function __construct(
        #[Autowire(env: 'json:GEOLOC_OBJECTS')]
        private array $geolocatableObjects,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function buildMapConfig(string $mapName): MapConfigInterface
    {
        $config = array_find($this->geolocatableObjects, static fn($object) => isset($object['name']) && $object['name'] === $mapName);
        if (!$config) {
            throw new MapConfigNotFoundException($mapName);
        }


        $objects = [];
        foreach ($config['objects'] ?? [] as $object) {
            $objects[] = new GeolocatableObject(
                name: $object['name'] ?? '',
                url: $object['url'] ?? '',
                method: 'GET',
                queryParams: $object['query_params'] ?? [],
                latitudeJsonPath: $object['latitude_json_path'] ?? '',
                longitudeJsonPath: $object['longitude_json_path'] ?? '',
                httpClient: $this->httpClient,
            );
        }

        return new MapConfig(
            $mapName,
            new Coordinates(
                latitude: $config['default_latitude'] ?? 0.,
                longitude: $config['default_longitude'] ?? 0.
            ),
            $config['default_zoom_level'] ?? 12,
            $config['refresh_interval'] ?? 5000, // in milliseconds
            ...$objects,
        );
    }

}
