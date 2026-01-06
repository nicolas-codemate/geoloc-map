<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\FrenchHolidayCalculator;
use App\Service\MapConfigBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MapConfigBuilderTest extends TestCase
{
    private FrenchHolidayCalculator $holidayCalculator;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->holidayCalculator = new FrenchHolidayCalculator();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testBuildMapConfigWithCustomMessage(): void
    {
        $geolocObjects = [
            [
                'mapName' => 'test_map',
                'default_latitude' => 48.8575,
                'default_longitude' => 2.3514,
                'default_zoom_level' => 12,
                'refresh_interval' => 5000,
                'custom_message' => 'Service indisponible actuellement',
                'objects' => [],
            ],
        ];

        $builder = new MapConfigBuilder(
            $geolocObjects,
            $this->httpClient,
            $this->holidayCalculator,
            new NullLogger()
        );

        $config = $builder->buildMapConfig('test_map');

        $this->assertSame('Service indisponible actuellement', $config->customMessage);
    }

    public function testBuildMapConfigWithDefaultMessageWhenCustomMessageNotProvided(): void
    {
        $geolocObjects = [
            [
                'mapName' => 'test_map',
                'default_latitude' => 48.8575,
                'default_longitude' => 2.3514,
                'default_zoom_level' => 12,
                'refresh_interval' => 5000,
                'objects' => [],
            ],
        ];

        $builder = new MapConfigBuilder(
            $geolocObjects,
            $this->httpClient,
            $this->holidayCalculator,
            new NullLogger()
        );

        $config = $builder->buildMapConfig('test_map');

        $this->assertSame('Aucune donnée de géolocalisation', $config->customMessage);
    }

    public function testBuildMapConfigIgnoresInvalidCustomMessageTypes(): void
    {
        $geolocObjects = [
            [
                'mapName' => 'test_map',
                'default_latitude' => 48.8575,
                'default_longitude' => 2.3514,
                'default_zoom_level' => 12,
                'refresh_interval' => 5000,
                'custom_message' => ['invalid', 'array'],
                'objects' => [],
            ],
        ];

        $builder = new MapConfigBuilder(
            $geolocObjects,
            $this->httpClient,
            $this->holidayCalculator,
            new NullLogger()
        );

        $config = $builder->buildMapConfig('test_map');

        $this->assertSame('Aucune donnée de géolocalisation', $config->customMessage);
    }

    public function testBuildMapConfigWithEmptyCustomMessage(): void
    {
        $geolocObjects = [
            [
                'mapName' => 'test_map',
                'default_latitude' => 48.8575,
                'default_longitude' => 2.3514,
                'default_zoom_level' => 12,
                'refresh_interval' => 5000,
                'custom_message' => '',
                'objects' => [],
            ],
        ];

        $builder = new MapConfigBuilder(
            $geolocObjects,
            $this->httpClient,
            $this->holidayCalculator,
            new NullLogger()
        );

        $config = $builder->buildMapConfig('test_map');

        // Empty string is a valid custom message (customer might want to show nothing)
        $this->assertSame('', $config->customMessage);
    }
}
