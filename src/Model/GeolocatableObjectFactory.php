<?php

declare(strict_types=1);


namespace App\Model;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocatableObjectFactory
{

    public function __construct(
        private HttpClientInterface $client,
    )
    {
    }


    public function createFromEnv(string $envVariable): GeolocatableObject {

    }

}
