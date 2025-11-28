<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for MapController HTTP responses.
 *
 * Note: LiveComponent state (isLoading, hasMarkers) is only updated after JavaScript hydration.
 * For testing time range logic, see TimeRangeIntegrationTest which tests the business logic directly.
 * These tests verify:
 * - Correct HTTP responses (200/404)
 * - Map configuration loading
 * - LiveComponent presence in the rendered HTML
 */
class MapControllerTest extends WebTestCase
{
    public function testMapNotFoundReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/non_existent_map');

        self::assertResponseStatusCodeSame(404);
    }

    public function testMapAlwaysOpenReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test_map_always_open');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="live"]');
        self::assertSelectorExists('.map-overlay');
    }

    public function testMapWeekdaysReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test_map_weekdays');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="live"]');
    }

    public function testMapHolidaysReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test_map_holidays');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="live"]');
    }

    public function testMapContainsLeafletMap(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test_map_always_open');

        self::assertResponseIsSuccessful();
        // Check that Leaflet map container is rendered
        self::assertSelectorExists('[data-controller~="symfony--ux-leaflet-map--map"]');
    }

    public function testMapHasRefreshInterval(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_always_open');

        self::assertResponseIsSuccessful();
        // Check that data-poll attribute is present for auto-refresh
        self::assertSelectorExists('[data-poll]');

        // Verify the polling action is set to refreshMap
        $liveComponent = $crawler->filter('[data-controller="live"]');
        $this->assertStringContainsString('refreshMap', $liveComponent->attr('data-poll'));
    }

    public function testMapDefaultHeightIsViewportHeight(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_always_open');

        self::assertResponseIsSuccessful();

        // Check that the map uses 100vh by default (no height query param)
        $mapElement = $crawler->filter('[data-controller~="symfony--ux-leaflet-map--map"]');
        $style = $mapElement->attr('style');
        $this->assertStringContainsString('height: 100vh', $style);

        // Check that the overlay also uses 100vh
        $html = $crawler->html();
        $this->assertStringContainsString('height: 100vh', $html);
    }

    public function testMapCustomHeightFromQueryParam(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_always_open?height=500');

        self::assertResponseIsSuccessful();

        // Check that the map uses the custom height in pixels
        $mapElement = $crawler->filter('[data-controller~="symfony--ux-leaflet-map--map"]');
        $style = $mapElement->attr('style');
        $this->assertStringContainsString('height: 500px', $style);

        // Check that the overlay also uses the custom height
        $html = $crawler->html();
        $this->assertStringContainsString('height: 500px', $html);
    }

    public function testMapCustomHeightDifferentValue(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/test_map_always_open?height=800');

        self::assertResponseIsSuccessful();

        // Check that the map uses 800px
        $mapElement = $crawler->filter('[data-controller~="symfony--ux-leaflet-map--map"]');
        $style = $mapElement->attr('style');
        $this->assertStringContainsString('height: 800px', $style);
    }
}

