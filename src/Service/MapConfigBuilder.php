<?php

declare(strict_types=1);


namespace App\Service;

use App\Exception\InvalidDayMatcherException;
use App\Exception\MapConfigNotFoundException;
use App\Model\Coordinates;
use App\Model\DayMatcher;
use App\Model\GeolocatableObject;
use App\Model\MapConfig;
use App\Model\MapConfigInterface;
use App\Model\TimeRange;
use App\Model\TimeRangeContainer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MapConfigBuilder
{
    /**
     * @param array<int, mixed> $geolocatableObjects
     */
    public function __construct(
        #[Autowire(env: 'json:GEOLOC_OBJECTS')]
        private array $geolocatableObjects,
        private HttpClientInterface $httpClient,
        private FrenchHolidayCalculator $holidayCalculator,
        private LoggerInterface $logger,
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
            foreach ($config['time_ranges'] as $index => $timeRange) {
                try {
                    $dayMatchers = $this->parseDayMatchers($timeRange['days'] ?? []);

                    if (empty($dayMatchers)) {
                        $this->logger->warning("Time range at index {$index} has no valid days, skipping", [
                            'map_name' => $mapName,
                            'time_range' => $timeRange,
                        ]);
                        continue;
                    }

                    $startTime = $timeRange['startTime'] ?? '00:00';
                    $endTime = $timeRange['endTime'] ?? '23:59';

                    $timeRanges[] = new TimeRange(
                        dayMatchers: $dayMatchers,
                        startTime: $startTime,
                        endTime: $endTime,
                    );
                } catch (\Throwable $e) {
                    $this->logger->error("Failed to parse time range at index {$index}, skipping", [
                        'map_name' => $mapName,
                        'time_range' => $timeRange,
                        'exception' => $e->getMessage(),
                    ]);
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
            $config['refresh_interval'] ?? 5000,
            new TimeRangeContainer(
                $this->holidayCalculator,
                ...$timeRanges,
            ),
            ...$objects,
        );
    }

    /**
     * @param array<int, string> $days
     * @return array<int, DayMatcher>
     */
    private function parseDayMatchers(array $days): array
    {
        $dayMatchers = [];

        foreach ($days as $day) {
            try {
                $dayMatchers[] = DayMatcher::fromString($day);
            } catch (InvalidDayMatcherException $e) {
                $this->logger->warning("Invalid day format: {$day}", [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $dayMatchers;
    }

}
