<?php

declare(strict_types=1);


namespace App\Twig\Components;

use App\Exception\InvalidCoordinateException;
use App\Exception\InvalidCoordinatePathException;
use App\Model\Coordinates;
use App\Model\GeolocatableObjectInterface;
use App\Model\MapConfigInterface;
use App\Service\MapConfigBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockAwareTrait;
use Symfony\Component\Clock\DatePoint;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Live\ComponentWithMapTrait;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Throwable;

#[AsLiveComponent]
final class MapLive
{
    use DefaultActionTrait;
    use ComponentWithMapTrait;
    use ClockAwareTrait;

    #[LiveProp]
    public ?string $mapName = null;
    #[LiveProp]
    public int $height = 500;
    #[LiveProp]
    public ?int $refreshInterval = null;
    #[LiveProp]
    public bool $hasMarkers = true;
    #[LiveProp]
    public bool $isLoading = true;

    public function __construct(
        private readonly MapConfigBuilder $mapConfigBuilder,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $geolocatedObjectsCache,
    ) {
    }

    protected function instantiateMap(): Map
    {
        assert($this->mapName !== null, 'mapName must be set');
        $mapConfig = $this->mapConfigBuilder->buildMapConfig($this->mapName);

        $this->refreshInterval = $mapConfig->refreshInterval;

        $map = new Map('default')
            ->center(
                new Point(
                    latitude: $mapConfig->defaultCoordinates->latitude,
                    longitude: $mapConfig->defaultCoordinates->longitude,
                )
            )
            ->zoom($mapConfig->defaultZoomLevel)
            ->fitBoundsToMarkers(false) // will handle this manually in javascript. See assets/controllers/map_controller.js
            ->options(
                new LeafletOptions()
                    ->tileLayer(
                        new TileLayer(
                            url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            options: ['maxZoom' => 19]
                        )
                    )
            );

        $this->fetchGeolocationData($map, $mapConfig);

        $this->isLoading = false;

        return $map;
    }

    #[LiveAction]
    public function refreshMap(): void
    {
        assert($this->mapName !== null, 'mapName must be set');
        $mapConfig = $this->mapConfigBuilder->buildMapConfig($this->mapName);

        $this->fetchGeolocationData($this->getMap(), $mapConfig);
    }

    private function fetchGeolocationData(Map $map, MapConfigInterface $mapConfig): void
    {
        $now = new DatePoint($this->now()->format(\DateTimeInterface::ATOM));
        if (false === $mapConfig->timeRangeContainer->matches($now)) {
            $this->hasMarkers = false;
            $this->logger->info(sprintf('Out of time ranges: %s', $mapConfig->timeRangeContainer));

            return;
        }

        $locatedObjectsCount = 0;
        foreach ($mapConfig->geolocatableObjects as $geolocatableObject) {
            $coordinates = $this->getObjectCoordinates($geolocatableObject, $mapConfig);
            if (null === $coordinates) {
                continue;
            }

            $map
                ->removeMarker($geolocatableObject->name)
                ->addMarker(
                    new Marker(
                        position: new Point(
                            latitude: $coordinates->latitude,
                            longitude: $coordinates->longitude,
                        ),
                        title: $geolocatableObject->name,
                        infoWindow: new InfoWindow(
                            content: sprintf('<h4>%s</h4>', $geolocatableObject->name),
                            opened: false, // manage opening in javascript
                        ),
                        id: $geolocatableObject->name
                    )
                );
            ++$locatedObjectsCount;
        }

        $this->hasMarkers = $locatedObjectsCount > 0;
    }

    private function getObjectCoordinates(GeolocatableObjectInterface $geolocatableObject, MapConfigInterface $mapConfig): ?Coordinates
    {
        $cacheKey = $this->generateCacheKey($geolocatableObject->name);

        if ($geolocatableObject->sandbox) {
            // if the object is sandboxed, we mock the coordinates base either on the default coordinates or on the previously cached coordinates
            $baseMockCoordinates = $mapConfig->defaultCoordinates;

            // check if the object is already cached
            $cachedItem = $this->geolocatedObjectsCache->getItem($cacheKey);
            if ($cachedItem->isHit()) {
                // if cached, use the cached coordinates as base for mocking
                $baseMockCoordinates = $cachedItem->get();
            }

            $coordinates = $geolocatableObject->mockCoordinates($baseMockCoordinates);
            $cachedItem->set($coordinates);
            $cachedItem->expiresAfter(3600); // cache for 1 hour for sandboxed objects
            $this->geolocatedObjectsCache->save($cachedItem);

            return $coordinates;
        }

        // check if the object is already cached. If it is, we return the cached coordinates, no need to fetch, to reduce load on the API. Cache is shared across all instances.
        $cachedItem = $this->geolocatedObjectsCache->getItem($cacheKey);
        if ($cachedItem->isHit()) {
            // if cached, return the cached coordinates
            return $cachedItem->get();
        }

        try {
            $coordinates = $geolocatableObject->fetchGeolocationData();
        } catch (HttpExceptionInterface|TransportExceptionInterface $exception) {
            $this->logger->critical(sprintf('Error fetching geolocation data: %s', $exception->getMessage()), [
                'url' => $geolocatableObject->url,
                'method' => $geolocatableObject->method,
                'queryParams' => $geolocatableObject->queryParams,
                'exception' => $exception,
            ]);

            return null;
        } catch (InvalidCoordinatePathException|InvalidCoordinateException $exception) {
            $this->logger->critical($exception->getMessage());

            return null;
        } catch (Throwable $exception) {
            $this->logger->critical(sprintf('Unexpected error: %s', $exception->getMessage()), [
                'url' => $geolocatableObject->url,
                'method' => $geolocatableObject->method,
                'queryParams' => $geolocatableObject->queryParams,
                'exception' => $exception,
            ]);

            return null;
        }

        $this->geolocatedObjectsCache->save(
            $cachedItem
                ->set($coordinates)
                ->expiresAfter($mapConfig->refreshInterval / 1000)
        );

        return $coordinates;
    }

    private function generateCacheKey(string $objectName): string
    {
        return 'geoloc_' . md5($objectName);
    }
}
