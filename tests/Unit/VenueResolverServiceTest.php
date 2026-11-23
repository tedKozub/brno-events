<?php

namespace Tests\Unit;

use App\Models\GeocodingCache;
use App\Models\Venue;
use App\Models\VenueAlias;
use App\Services\VenueResolverService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VenueResolverServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected VenueResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(VenueResolverService::class);
        VenueResolverService::flushDictionary();
    }

    public function test_extracts_rooms_and_clean_locations(): void
    {
        $res1 = $this->resolver->extractRoomAndLocation('Budova M, místnost M2.12, Areál MENDELU, Zemědělská 1, Brno');
        $this->assertStringContainsString('Budova M', $res1['room']);
        $this->assertStringContainsString('místnost M2.12', $res1['room']);
        $this->assertStringContainsString('Areál MENDELU', $res1['clean_location']);

        $res2 = $this->resolver->extractRoomAndLocation('Božetěchova 1/2, FIT VUT, posluchárna D105');
        $this->assertEquals('posluchárna D105', $res2['room']);
        $this->assertEquals('Božetěchova 1/2, FIT VUT', $res2['clean_location']);

        $res3 = $this->resolver->extractRoomAndLocation('Štefánikova 24, Fléda');
        $this->assertNull($res3['room']);
        $this->assertEquals('Štefánikova 24, Fléda', $res3['clean_location']);
    }

    public function test_resolves_known_venue_by_alias(): void
    {
        $venue = Venue::firstOrCreate(['slug' => 'fit-vut-bozetechova'], [
            'name' => 'FIT VUT',
            'address' => 'Božetěchova 1/2',
            'city' => 'Brno',
            'latitude' => 49.2268,
            'longitude' => 16.5969,
        ]);
        $this->resolver->registerAlias($venue, 'Kachnička');
        $this->resolver->registerAlias($venue, 'FIT VUT');

        $resolved = $this->resolver->resolve('Kachnička, Božetěchova');
        $this->assertNotNull($resolved);
        $this->assertEquals($venue->id, $resolved->id);

        $resolved2 = $this->resolver->resolve('Posluchárna D105, FIT VUT');
        $this->assertNotNull($resolved2);
        $this->assertEquals($venue->id, $resolved2->id);
    }

    public function test_resolves_ukb_with_room_detail(): void
    {
        $kampus = Venue::firstOrCreate(['slug' => 'kampus-bohunice'], [
            'name' => 'Univerzitní kampus Bohunice',
            'address' => 'Kamenice 5',
            'city' => 'Brno',
            'latitude' => 49.1772,
            'longitude' => 16.5684,
        ]);
        $this->resolver->registerAlias($kampus, 'ukb');
        $this->resolver->registerAlias($kampus, 'recepce ukb');

        // Test extraction of parenthesized building/room
        $extracted = $this->resolver->extractRoomAndLocation('Recepce u hlavního vstupu UKB (B22)');
        $this->assertEquals('B22', $extracted['room']);
        $this->assertEquals('Recepce u hlavního vstupu UKB', $extracted['clean_location']);

        // Test venue resolution
        $resolved = $this->resolver->resolve('Recepce u hlavního vstupu UKB (B22)');
        $this->assertNotNull($resolved);
        $this->assertEquals($kampus->id, $resolved->id);
    }

    public function test_detects_locations_outside_brno(): void
    {
        $this->assertTrue($this->resolver->isExplicitlyOutsideBrno('Valtická 337, Lednice', 'Zahradnická fakulta'));
        $this->assertTrue($this->resolver->isExplicitlyOutsideBrno('Telč', 'Univerzitní centrum'));
        $this->assertTrue($this->resolver->isExplicitlyOutsideBrno('Praha', 'Konference na UK'));

        $this->assertFalse($this->resolver->isExplicitlyOutsideBrno('Bratislavská 10, Brno', 'Klub v Brně'));
        $this->assertFalse($this->resolver->isExplicitlyOutsideBrno('Pražská 38, Brno', 'Kavárna'));
    }

    public function test_geocoding_cache_hash_and_storage(): void
    {
        $hash1 = GeocodingCache::hashQuery('Dominikánské náměstí 1, Brno');
        $hash2 = GeocodingCache::hashQuery('  dominikánské   náměstí 1, BRNO ');
        $this->assertEquals($hash1, $hash2);

        GeocodingCache::create([
            'query_hash' => $hash1,
            'raw_query' => 'Dominikánské náměstí 1, Brno',
            'latitude' => 49.1937,
            'longitude' => 16.6066,
            'osm_place_id' => 999111,
            'formatted_address' => 'Nová radnice, Dominikánské náměstí 1, Brno',
        ]);

        $geo = $this->resolver->geocode('Dominikánské náměstí 1, Brno');
        $this->assertNotNull($geo);
        $this->assertEquals(49.1937, $geo['lat']);
        $this->assertEquals(16.6066, $geo['lng']);
        $this->assertEquals(999111, $geo['osm_place_id']);
    }

    public function test_within_brno_bounds(): void
    {
        $this->assertTrue($this->resolver->isWithinBrnoBounds(49.1951, 16.6068)); // Náměstí Svobody
        $this->assertTrue($this->resolver->isWithinBrnoBounds(49.2268, 16.5969)); // Královo Pole
        $this->assertFalse($this->resolver->isWithinBrnoBounds(50.0875, 14.4213)); // Praha
        $this->assertFalse($this->resolver->isWithinBrnoBounds(48.7997, 16.8043)); // Lednice
    }
}
