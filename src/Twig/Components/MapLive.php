<?php

declare(strict_types=1);


namespace App\Twig\Components;

use App\Exception\InvalidCoordinateException;
use App\Exception\InvalidCoordinatePathException;
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
    public ?int $height = 500;
    public ?MapConfigInterface $config = null;

    public function __construct(
        private readonly MapConfigBuilder $mapConfigBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function instantiateMap(): Map
    {
        $mapConfig = $this->mapConfigBuilder->buildMapConfig($this->mapName);

        $map = new Map('default')
            ->center(
                new Point(
                    latitude: $mapConfig->defaultCoordinates->latitude,
                    longitude: $mapConfig->defaultCoordinates->longitude,
                )
            )
            ->zoom($mapConfig->defaultZoomLevel)
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

        // out of time ranges, act like there are no markers
        if (false === $mapConfig->timeRangeContainer->isCurrentTimeInRanges()) {
            $this->logger->info(sprintf('Out of time ranges: %s', $mapConfig->timeRangeContainer));

            return $map->fitBoundsToMarkers();
        }

        $this->config = $mapConfig;

        return $map;
    }


    #[LiveAction]
    public function fetchGeolocationData(): void
    {
        foreach ($this->config->geolocatableObjects as $geolocatableObject) {
            try {
                $coordinates = $geolocatableObject->fetchGeolocationData();
            } catch (HttpExceptionInterface|TransportExceptionInterface $exception) {
                $this->logger->critical(sprintf('Error fetching geolocation data: %s', $exception->getMessage()), [
                    'url' => $geolocatableObject->url,
                    'method' => $geolocatableObject->method,
                    'queryParams' => $geolocatableObject->queryParams,
                    'exception' => $exception,
                ]);
                continue;
            } catch (InvalidCoordinatePathException|InvalidCoordinateException $exception) {
                $this->logger->critical($exception->getMessage());
                continue;
            } catch (\Throwable $exception) {
                $this->logger->critical(sprintf('Unexpected error: %s', $exception->getMessage()), [
                    'url' => $geolocatableObject->url,
                    'method' => $geolocatableObject->method,
                    'queryParams' => $geolocatableObject->queryParams,
                    'exception' => $exception,
                ]);
                continue;
            }

            if (null === $coordinates) {
                return;
            }

            $this->getMap()
                ->addMarker(
                    new Marker(
                        position: new Point(
                            latitude: $coordinates->latitude,
                            longitude: $coordinates->longitude,
                        ),
                        title: $geolocatableObject->name,
                        infoWindow: new InfoWindow(
                            content: sprintf('<h2>%s</h2>', $geolocatableObject->name),
                            opened: true,
                        )
                    )
                );
        }
    }
}
