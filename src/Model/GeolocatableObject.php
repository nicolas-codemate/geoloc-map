<?php

declare(strict_types=1);


namespace App\Model;

use Symfony\Component\Clock\DatePoint;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocatableObject implements GeolocatableObjectInterface
{
    public function __construct(
        public string $name,
        public string $url,
        public string $method,
        public array $queryParams,
        public string $latitudeJsonPath,
        public string $longitudeJsonPath,
        public HttpClientInterface $httpClient
    ) {
    }

    public function fetchGeolocationData(): ?Coordinates
    {
        $response = $this->httpClient->request($this->method, $this->url, [
            'query' => $this->queryParams,
            'headers' => [
                'ngrok-skip-browser-warning' => '1',
            ],
            'verify_peer' => false, // not sure we should keep this. Or maybe in a configuration option
            'verify_host' => false, // not sure we should keep this. Or maybe in a configuration option
        ]);

        $data = $response->toArray();

        $latidue = $data[$this->latitudeJsonPath] ?? null;
        $longitude = $data[$this->longitudeJsonPath] ?? null;

        if (!$latidue || !$longitude) {
            return null;
        }

        return new Coordinates(
            latitude: (float)$data[$this->latitudeJsonPath],
            longitude: (float)$data[$this->longitudeJsonPath],
            dateTime: new DatePoint(),
        );
    }
}
