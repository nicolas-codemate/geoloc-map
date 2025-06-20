<?php

declare(strict_types=1);


namespace App\Twig\Components;

use App\Exception\InvalidCoordinateException;
use App\Exception\InvalidCoordinatePathException;
use App\Model\Coordinates;
use App\Model\GeolocatableObjectInterface;
use App\Model\MapConfigInterface;
use App\Service\MapConfigBuilder;
use Psr\Log\LoggerInterface;
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

#[AsLiveComponent]
final class MapLive
{
    use DefaultActionTrait;
    use ComponentWithMapTrait;

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
    ) {
    }

    protected function instantiateMap(): Map
    {
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
        $mapConfig = $this->mapConfigBuilder->buildMapConfig($this->mapName);

        $this->fetchGeolocationData($this->getMap(), $mapConfig);
    }

    private function fetchGeolocationData(Map $map, MapConfigInterface $mapConfig): void
    {
        if (false === $mapConfig->timeRangeContainer->isCurrentTimeInRanges()) {
            $this->hasMarkers = false;
            $this->logger->info(sprintf('Out of time ranges: %s', $mapConfig->timeRangeContainer));

            return;
        }

        $locatedObjectsCount = 0;
        foreach ($mapConfig->geolocatableObjects as $geolocatableObject) {
            $coordinates = $this->getObjectCoordinates($geolocatableObject, $mapConfig->defaultCoordinates);
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
                            opened: true,
                        ),
                        id: $geolocatableObject->name
                    )
                );
            ++$locatedObjectsCount;
        }

        $this->hasMarkers = $locatedObjectsCount > 0;

        if (!$this->hasMarkers) {
            return;
        }

//        if (1 === $locatedObjectsCount && isset($coordinates)) {
//            $map->center(
//                new Point(
//                    latitude: $coordinates->latitude + 0.0005, // small offset to avoid zooming in too much
//                    longitude: $coordinates->longitude - 0.0005, // small offset to avoid zooming in too much
//                )
//            );
//        }
    }

    private function getObjectCoordinates(GeolocatableObjectInterface $geolocatableObject, Coordinates $defaultCoordinates): ?Coordinates
    {
        if ($geolocatableObject->sandbox) {
            return $geolocatableObject->mockCoordinates($defaultCoordinates);
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
        } catch (\Throwable $exception) {
            $this->logger->critical(sprintf('Unexpected error: %s', $exception->getMessage()), [
                'url' => $geolocatableObject->url,
                'method' => $geolocatableObject->method,
                'queryParams' => $geolocatableObject->queryParams,
                'exception' => $exception,
            ]);

            return null;
        }

        return $coordinates;
    }
}
