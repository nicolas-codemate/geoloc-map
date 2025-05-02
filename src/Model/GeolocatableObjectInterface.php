<?php

declare(strict_types=1);


namespace App\Model;

use Symfony\Contracts\HttpClient\HttpClientInterface;

interface GeolocatableObjectInterface
{
    public string $name {
        get;
    }

    public HttpClientInterface $httpClient {
        get;
    }

    public function fetchGeolocationData(): Coordinates;
}
