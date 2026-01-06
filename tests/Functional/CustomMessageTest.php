<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Model\MapConfig;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * Functional tests for custom message display.
 *
 * Tests verify that the correct message is configured when:
 * - Outside of configured time ranges
 * - No geolocation data is available
 *
 * Note: LiveComponent renders with initial state (isLoading=true, hasMarkers=true),
 * then updates props after instantiateMap(). We test the final props values
 * serialized in data-live-props-value attribute.
 */
class CustomMessageTest extends WebTestCase
{
    use ClockSensitiveTrait;

    public function testDefaultMessageWhenAlwaysClosed(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_always_closed');

        self::assertResponseIsSuccessful();

        $props = $this->extractLiveProps($crawler);

        $this->assertFalse($props['hasMarkers'], 'hasMarkers should be false when always closed');
        $this->assertSame(MapConfig::DEFAULT_CUSTOM_MESSAGE, $props['customMessage']);
    }

    public function testCustomMessageWhenOutsideTimeRangesOnWeekend(): void
    {
        // Mock time to be on a Saturday (outside weekday hours)
        static::mockTime(new DateTimeImmutable('2025-06-14 10:00:00'));

        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_custom_message');

        self::assertResponseIsSuccessful();

        $props = $this->extractLiveProps($crawler);

        $this->assertFalse($props['hasMarkers'], 'hasMarkers should be false on weekend');
        $this->assertSame('Service temporairement indisponible', $props['customMessage']);
    }

    public function testCustomMessageWhenOutsideBusinessHours(): void
    {
        // Mock time to be on a weekday but outside business hours (before 08:00)
        static::mockTime(new DateTimeImmutable('2025-06-10 06:00:00'));

        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_custom_message');

        self::assertResponseIsSuccessful();

        $props = $this->extractLiveProps($crawler);

        $this->assertFalse($props['hasMarkers'], 'hasMarkers should be false before business hours');
        $this->assertSame('Service temporairement indisponible', $props['customMessage']);
    }

    public function testCustomMessageWhenNoObjects(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_no_objects');

        self::assertResponseIsSuccessful();

        $props = $this->extractLiveProps($crawler);

        $this->assertFalse($props['hasMarkers'], 'hasMarkers should be false when no objects');
        $this->assertSame('Aucun véhicule disponible', $props['customMessage']);
    }

    public function testHasMarkersTrueDuringBusinessHours(): void
    {
        // Mock time to be during business hours
        static::mockTime(new DateTimeImmutable('2025-06-10 10:00:00'));

        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_custom_message');

        self::assertResponseIsSuccessful();

        $props = $this->extractLiveProps($crawler);

        // During business hours with sandbox objects, hasMarkers should be true
        $this->assertTrue($props['hasMarkers'], 'hasMarkers should be true during business hours');
        // Custom message is still set but won't be displayed
        $this->assertSame('Service temporairement indisponible', $props['customMessage']);
    }

    public function testDefaultMessageUsedWhenCustomMessageNotConfigured(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_weekdays');

        self::assertResponseIsSuccessful();

        $props = $this->extractLiveProps($crawler);

        // Default message should be used when custom_message is not in config
        $this->assertSame(MapConfig::DEFAULT_CUSTOM_MESSAGE, $props['customMessage']);
    }

    /**
     * Extract LiveComponent props from the data-live-props-value attribute.
     *
     * @return array<string, mixed>
     */
    private function extractLiveProps(\Symfony\Component\DomCrawler\Crawler $crawler): array
    {
        $liveComponent = $crawler->filter('[data-live-props-value]');
        $this->assertCount(1, $liveComponent, 'Expected exactly one LiveComponent');

        $propsJson = $liveComponent->attr('data-live-props-value');
        $this->assertNotNull($propsJson, 'data-live-props-value should not be null');

        $props = json_decode($propsJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($props);

        return $props;
    }
}
