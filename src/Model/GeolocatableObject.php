<?php

declare(strict_types=1);


namespace App\Model;

use App\Exception\InvalidCoordinateException;
use App\Exception\InvalidCoordinatePathException;
use Symfony\Component\Clock\DatePoint;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocatableObject implements GeolocatableObjectInterface
{
    /**
     * @param array<string, mixed> $queryParams
     */
    public function __construct(
        public string $name,
        public string $url,
        public string $method,
        public array $queryParams,
        public string $latitudeJsonPath,
        public string $longitudeJsonPath,
        public HttpClientInterface $httpClient,
        public bool $sandbox,
    ) {
    }

    public function fetchGeolocationData(): ?Coordinates
    {
        $response = $this->httpClient->request($this->method, $this->url, [
            'headers' => [
                'ngrok-skip-browser-warning' => 0, // to ease testing
            ],
            'query' => $this->queryParams,
        ]);

        $data = $response->toArray();

        if (!isset($data[$this->latitudeJsonPath], $data[$this->longitudeJsonPath])) {
            throw new InvalidCoordinatePathException(
                sprintf(
                    'Could not retrieve latitude or longitude from path (latitude: "%s", longitude : "%s"). Response data was : "%s"',
                    $this->latitudeJsonPath,
                    $this->longitudeJsonPath,
                    json_encode($data)
                )
            );
        }

        $latitude = $data[$this->latitudeJsonPath];
        $longitude = $data[$this->longitudeJsonPath];

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new InvalidCoordinateException(
                sprintf(
                    'Invalid coordinate values (latitude: "%s", longitude : "%s").',
                    $latitude,
                    $longitude,
                )
            );
        }

        return new Coordinates(
            latitude: (float)$data[$this->latitudeJsonPath],
            longitude: (float)$data[$this->longitudeJsonPath],
            dateTime: new DatePoint(),
        );
    }

    public function mockCoordinates(Coordinates $baseCoordinates): Coordinates
    {
        // introduce a small random variation, but occasionally use a larger one
        $variationFactor = random_int(1, 10) === 10 ? 5_000 : 30_000;

        $deltaLatitude = (random_int(1, 9) / $variationFactor) * (random_int(0, 1) ? 1 : -1);
        $deltaLongitude = (random_int(1, 9) / $variationFactor) * (random_int(0, 1) ? 1 : -1);

        $newLatitude = $baseCoordinates->latitude + $deltaLatitude;
        $newLongitude = $baseCoordinates->longitude + $deltaLongitude;

        return new Coordinates(
            latitude: $newLatitude,
            longitude: $newLongitude,
            dateTime: new DatePoint(),
        );
    }
}
