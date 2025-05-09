<?php

declare(strict_types=1);


namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\Map\Live\ComponentWithMapTrait;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

#[AsLiveComponent]
final class MapLive
{
    use DefaultActionTrait;
    use ComponentWithMapTrait;

    protected function instantiateMap(): \Symfony\UX\Map\Map
    {
        return (new Map())
            ->center(new Point(48.8566, 2.3522))
            ->zoom(7)
            ->addMarker(new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', infoWindow: new InfoWindow('Paris')))
            ->addMarker(new Marker(position: new Point(45.75, 4.85), title: 'Lyon', infoWindow: new InfoWindow('Lyon')));
    }
}
