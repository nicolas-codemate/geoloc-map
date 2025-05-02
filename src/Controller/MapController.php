<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\MapConfigNotFoundException;
use App\Service\MapConfigBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

class MapController extends AbstractController
{
    #[Route('/{name}', name: 'map')]
    public function __invoke(MapConfigBuilder $configBuilder, string $name, LoggerInterface $logger): Response
    {
        try {
            $mapConfig = $configBuilder->buildMapConfig($name);
        } catch (MapConfigNotFoundException) {
            throw $this->createNotFoundException();
        }

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

        foreach ($mapConfig->geolocatableObjects as $geolocatableObject) {
            try {
                $coordinates = $geolocatableObject->fetchGeolocationData();
            } catch (HttpExceptionInterface|TransportExceptionInterface $exception) {
                $logger->critical(sprintf('Error fetching geolocation data: %s', $exception->getMessage()), [
                    'url' => $geolocatableObject->url,
                    'method' => $geolocatableObject->method,
                    'queryParams' => $geolocatableObject->queryParams,
                    'exception' => $exception,
                ]);
                throw $exception;
//                throw $this->createNotFoundException();
            }
            $map
                ->addMarker(
                    new Marker(
                        position: new Point(
                            latitude: $coordinates->latitude,
                            longitude: $coordinates->longitude,
                        ),
                        title: $geolocatableObject->name,
                        infoWindow: new InfoWindow(
                            content: sprintf('<p>%s</p>', $geolocatableObject->name),
                        )
                    )
                );
        }

        return $this->render('ux_packages/map.html.twig', [
            'map' => $map
                ->fitBoundsToMarkers()
            ,
        ]);
    }
}
