<?php

declare(strict_types=1);


namespace App\Service;

use App\Exception\MapConfigNotFoundException;
use App\Model\Coordinates;
use App\Model\GeolocatableObject;
use App\Model\MapConfig;
use App\Model\MapConfigInterface;
use App\Model\TimeRange;
use App\Model\TimeRangeContainer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MapConfigBuilder
{
    public function __construct(
        #[Autowire(param: 'geoloc_objects')]
        private array $geolocatableObjects,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function buildMapConfig(string $mapName): MapConfigInterface
    {
        $config = array_find($this->geolocatableObjects, static fn($object) => isset($object['mapName']) && $object['mapName'] === $mapName);
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
                sandbox: isset($object['enable_sandbox']) && $object['enable_sandbox']
            );
        }

        $timeRanges = [];
        if (isset($config['time_ranges']) && is_array($config['time_ranges'])) {
            foreach ($config['time_ranges'] as $timeRange) {
                if (isset($timeRange['startTime'], $timeRange['endTime'])) {
                    $days = $timeRange['days'] ?? null;

                    $timeRanges[] = new TimeRange(
                        days: $days,
                        startTime: $timeRange['startTime'],
                        endTime: $timeRange['endTime'],
                    );
                }
            }
        }

        return new MapConfig(
            $mapName,
            new Coordinates(
                latitude: $config['default_latitude'] ?? 0.,
                longitude: $config['default_longitude'] ?? 0.
            ),
            $config['default_zoom_level'] ?? 12,
            $config['refresh_interval'] ?? 5000, // in milliseconds
            new TimeRangeContainer(
                ...$timeRanges,
            ),
            ...$objects,
        );
    }

}
