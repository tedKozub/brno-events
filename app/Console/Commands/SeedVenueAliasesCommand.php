<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Venue;
use App\Models\VenueAlias;
use App\Services\VenueResolverService;
use Illuminate\Console\Command;

class SeedVenueAliasesCommand extends Command
{
    protected $signature = 'venues:seed-aliases {--resolve-events : Znovu projde všechny existující akce a spáruje je}';
    protected $description = 'Naplní databázi aliasů pro brněnská místa (fakulty, kluby, areály) a volitelně přepáruje akce';

    public function handle(VenueResolverService $resolver): int
    {
        $this->info('Spouštím naplnění aliasů pro brněnská místa konání...');

        // 1. Zajištění klíčových chybějících fakult / míst
        $this->ensureEssentialVenues();

        // 2. Mapa aliasů pro známá místa
        $aliasMap = [
            'fleda-club' => [
                'fléda', 'fleda', 'fleda club', 'fleda music club', 'štefánikova 24', 'stefanikova 24',
            ],
            'artbar-druhy-pad' => [
                'artbar', 'artbar druhý pád', 'artbar druhy pad', 'druhý pád', 'druhy pad', 'štefánikova 1',
            ],
            'fit-vut-bozetechova' => [
                'fit', 'fit vut', 'fakulta informačních technologií', 'fakulta informacnich technologii',
                'božetěchova', 'bozetechova', 'božetěchova 1/2', 'kachnička', 'kachnicka',
                'starý pivovar božetěchova', 'aula fit',
            ],
            'fekt-vut-technicka' => [
                'fekt vut', 'fekt', 'fakulta elektrotechniky a komunikačních technologií',
                'technická 12', 'technicka 12', 'aula profesora braunera', 'aula prof. braunera',
            ],
            'fsi-vut-technicka' => [
                'fsi vut', 'fsi', 'fakulta strojního inženýrství', 'fakulta strojniho inzenyrstvi',
                'technická 2', 'technicka 2', 'technická 2896/2', 'aula q fsi',
            ],
            'areal-mendelu-zemedelska' => [
                'mendelu', 'areál mendelu', 'areal mendelu', 'zemědělská 1', 'zemedelska 1',
                'zemědělská', 'zemedelska', 'provozně ekonomická fakulta mendelu', 'pef mendelu',
                'agronomická fakulta mendelu', 'af mendelu', 'lesnická a dřevařská fakulta', 'ldf mendelu',
                'fakulta regionálního rozvoje', 'frrms mendelu',
            ],
            'kampus-bohunice' => [
                'ukb', 'kampus bohunice', 'univerzitní kampus bohunice', 'univerzitni kampus bohunice',
                'recepce ukb', 'vstup ukb', 'hlavní vstup ukb', 'hlavni vstup ukb',
                'kampus muni', 'kamenice', 'kamenice 5', 'b22', 'b11', 'b11/', 'simu', 'simulační centrum',
                'simulacni centrum', 'fn bohunice', 'ceitec ukb', 'ceitec bohunice', 'simu ukb',
                'přírodovědecká fakulta bohunice', 'lékařská fakulta bohunice', 'lf muni', 'fsps muni',
                'fakulta sportovních studií', 'fakulta sportovnich studii', 'ceitec muni', 'biocev bohunice',
                'bohunický kampus', 'bohunicky kampus',
            ],
            'fi-muni-botanicka' => [
                'fi muni', 'fi mu', 'fakulta informatiky', 'botanická 68', 'botanická 68a',
                'botanicka 68', 'aula fi muni', 'cerit-sp',
            ],
            'prf-muni-kotlarska' => [
                'přf muni', 'prf muni', 'přírodovědecká fakulta kotlářská', 'kotlářská 2',
                'kotlarska 2', 'kotlářská', 'kotlarska', 'aula přf muni',
            ],
            'fss-muni-jostova' => [
                'fss', 'fss muni', 'fss mu', 'fakulta sociálních studií', 'fakulta socialnich studii',
                'joštova 10', 'jostova 10', 'joštova', 'jostova', 'aula fss',
            ],
            'ff-muni-arne-novaka' => [
                'ff muni', 'ff mu', 'filozofická fakulta', 'filozoficka fakulta',
                'arne nováka', 'arne novaka', 'arne nováka 1', 'arne nováka 1/1', 'arne novaka 1',
                'arna nováka 1', 'arna nováka', 'arna novaka',
            ],
            'clubco-vlnena' => [
                'clubco', 'clubco vlněna', 'clubco vlnena', 'vlněna clubco', 'vlnena clubco',
                'vlněna', 'vlnena', 'přízova 5', 'prizova 5', 'přízova', 'prizova',
            ],
            'law-muni-veveri' => [
                'právnická fakulta', 'pravnicka fakulta', 'law muni', 'veveří 70',
                'veveri 70', 'aula právnické fakulty',
            ],
            'econ-muni-lipova' => [
                'esf muni', 'econ muni', 'ekonomicko-správní fakulta', 'ekonomicko-spravni fakulta',
                'lipová 41a', 'lipova 41a', 'lipová', 'lipova',
            ],
            'pdf-muni-porici' => [
                'pdf muni', 'pedagogická fakulta', 'poříčí 7/9', 'poříčí 9', 'poříčí 7',
                'poříčí 31', 'porici',
            ],
            'koleje-vinarska' => [
                'vinařská', 'vinarska', 'koleje vinařská', 'menza vinařská', 'vinařská 5',
            ],
            'koleje-ppv' => [
                'palackého vrchem', 'kolejní 2', 'kolejni 2', 'ppv', 'koleje ppv', 'menza ppv',
            ],
            'univerzitni-kino-scala' => [
                'scala', 'kino scala', 'univerzitní kino scala', 'moravské nám. 3', 'moravské náměstí 3',
            ],
            'kc-babylon' => [
                'babylon', 'kc babylon', 'kounicova 20',
            ],
            'koma-lounge-bar' => [
                'koma', 'koma lounge', 'koma bar', 'kounicova 42',
            ],
            'cafe-morgal' => [
                'morgal', 'café morgal', 'moravské náměstí 1a', 'místodržitelský palác',
            ],
            'skautsky-institut-brno' => [
                'skautský institut', 'skautsky institut', 'moravské náměstí 12',
            ],
            'kabinet-muz' => [
                'kabinet múz', 'kabinet muz', 'sukova 4',
            ],
            'music-lab' => [
                'music lab', 'opletalova 1',
            ],
            'kavarna-trojka' => [
                'kavárna trojka', 'trojka', 'dům pánů z kunštátu', 'dominikánská 9',
            ],
            'klub-selepka' => [
                'šelepka', 'selepka', 'klub šelepka', 'šelepova 1',
            ],
            'sono-music-club' => [
                'sono', 'sono centrum', 'sono music club', 'veveří 113', 'veveri 113',
            ],
            'perpetuum-club' => [
                'perpetuum', 'perpetuum club', 'rooseveltova 9',
            ],
            'maison-club-lounge' => [
                'maison', 'maison club', 'běhounská 9',
            ],
            'otevrena-zahrada' => [
                'otevřená zahrada', 'otevrena zahrada', 'údolní 33', 'nadace partnerství',
            ],
            'impact-hub-brno' => [
                'impact hub', 'impact hub brno', 'cyrilská 7',
            ],
            'park-luzanky' => [
                'lužánky', 'luzanky', 'park lužánky', 'středisko volného času lužánky', 'svč lužánky',
            ],
            'kasarna-cerna-pole-unob' => [
                'kasárna černá pole', 'univerzita obrany', 'unob', 'tř. generála píky', 'šumavská', 'kasarna cerna pole',
            ],
            'kampus-vetuni-palackeho' => [
                'vetuni', 'veterinární univerzita', 'palackého tř. 1946/1', 'palackého třída 1', 'areál vetuni',
            ],
            'nova-radnice' => [
                'nová radnice', 'nova radnice', 'dominikánské náměstí 1', 'sněmovní sál', 'křížová chodba',
            ],
            'rektorat-muni' => [
                'rektorát muni', 'žerotínovo náměstí 9', 'zerotinovo namesti 9', 'kounicův palác',
            ],
            'fast-vut-veveri' => [
                'fast vut', 'fakulta stavební vut', 'fakulta stavebni vut', 'veveří 95', 'veveri 95', 'žižkova 17',
            ],
            'mzk-kounicova' => [
                'moravská zemská knihovna', 'moravska zemska knihovna', 'mzk', 'kounicova 65a',
            ],
            'hvezdarna-brno' => [
                'hvězdárna a planetárium brno', 'hvezdarna a planetarium brno', 'hvězdárna brno', 'digitárium', 'kraví hora 2',
            ],
            'fn-u-sv-anny' => [
                'fn u sv. anny', 'fakultní nemocnice u sv. anny', 'pekařská 53', 'sv. anna', 'nemocnice u svaté anny',
            ],
        ];

        $insertedCount = 0;

        foreach ($aliasMap as $slug => $aliases) {
            $venue = Venue::where('slug', $slug)->first();
            if (!$venue) {
                continue;
            }

            foreach ($aliases as $alias) {
                $norm = VenueAlias::normalize($alias);
                $exists = VenueAlias::where('venue_id', $venue->id)
                    ->where('alias_normalized', $norm)
                    ->exists();

                if (!$exists) {
                    VenueAlias::create([
                        'venue_id' => $venue->id,
                        'alias' => $alias,
                        'alias_normalized' => $norm,
                    ]);
                    $insertedCount++;
                }
            }
        }

        VenueResolverService::flushDictionary();
        $this->info(" -> Úspěšně vloženo {$insertedCount} nových aliasů.");

        // 3. Volitelné přepárování stávajících akcí
        if ($this->option('resolve-events')) {
            $this->resolveAllEvents($resolver);
        }

        return 0;
    }

    protected function ensureEssentialVenues(): void
    {
        $essentials = [
            [
                'name' => 'Fakulta stavební VUT (FAST)',
                'slug' => 'fast-vut-veveri',
                'address' => 'Veveří 331/95',
                'city' => 'Brno',
                'latitude' => 49.2062,
                'longitude' => 16.5938,
                'place_type' => 'faculty',
            ],
            [
                'name' => 'Moravská zemská knihovna (MZK)',
                'slug' => 'mzk-kounicova',
                'address' => 'Kounicova 65a',
                'city' => 'Brno',
                'latitude' => 49.2085,
                'longitude' => 16.5935,
                'place_type' => 'library',
            ],
            [
                'name' => 'Hvězdárna a planetárium Brno',
                'slug' => 'hvezdarna-brno',
                'address' => 'Kraví hora 2/522',
                'city' => 'Brno',
                'latitude' => 49.2044,
                'longitude' => 16.5833,
                'place_type' => 'other',
            ],
            [
                'name' => 'Fakultní nemocnice u sv. Anny',
                'slug' => 'fn-u-sv-anny',
                'address' => 'Pekařská 664/53',
                'city' => 'Brno',
                'latitude' => 49.1913,
                'longitude' => 16.5985,
                'place_type' => 'other',
            ],
            [
                'name' => 'Clubco Vlněna',
                'slug' => 'clubco-vlnena',
                'address' => 'Přízova 5',
                'city' => 'Brno',
                'latitude' => 49.1896,
                'longitude' => 16.6174,
                'place_type' => 'coworking',
            ],
        ];

        foreach ($essentials as $item) {
            Venue::firstOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }
    }

    protected function resolveAllEvents(VenueResolverService $resolver): void
    {
        $this->info('Spouštím přepárování a extrakci učeben pro všechny akce v DB...');

        $events = Event::with('venue')->get();
        $matchedCount = 0;
        $roomsExtracted = 0;

        foreach ($events as $event) {
            $rawLoc = $event->location_name;

            // Extrakce místnosti
            $extracted = $resolver->extractRoomAndLocation($rawLoc);
            $room = $extracted['room'];
            $cleanLoc = $extracted['clean_location'];

            $updates = [];

            if ($room && $event->room_detail !== $room) {
                $updates['room_detail'] = $room;
                $roomsExtracted++;
            }

            // Pokud akce nemá venue, nebo chceme ověřit stávající
            $venue = $resolver->resolve($rawLoc, null, $event->title . ' ' . $event->description);
            if ($venue && $event->venue_id !== $venue->id) {
                $updates['venue_id'] = $venue->id;
                $matchedCount++;
            }

            if (!empty($updates)) {
                $event->update($updates);
            }
        }

        $this->info(" -> Hotovo: {$matchedCount} akcí získalo/aktualizovalo přiřazené místo, {$roomsExtracted} akcí má extrahovanou konkrétní učebnu/sál.");
    }
}
