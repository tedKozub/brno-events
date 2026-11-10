<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Venues in Brno
        $fleda = Venue::create([
            'name' => 'Fléda Club',
            'slug' => 'fleda-club',
            'address' => 'Štefánikova 24',
            'city' => 'Brno',
            'latitude' => 49.2096,
            'longitude' => 16.6033,
            'website_url' => 'https://fleda.cz',
            'description' => 'Legendární brněnský hudební klub.',
        ]);

        $artbar = Venue::create([
            'name' => 'ArtBar Druhý Pád',
            'slug' => 'artbar-druhy-pad',
            'address' => 'Štefánikova 1',
            'city' => 'Brno',
            'latitude' => 49.2062,
            'longitude' => 16.6042,
            'website_url' => 'https://artbar.cz',
            'description' => 'Alternativní prostor pro koncerty, divadlo a studentské večery.',
        ]);

        $vinarska = Venue::create([
            'name' => 'Koleje Vinařská – Menza & Klub',
            'slug' => 'koleje-vinarska',
            'address' => 'Vinařská 5',
            'city' => 'Brno',
            'latitude' => 49.1931,
            'longitude' => 16.5815,
            'description' => 'Centrum studentského kolejního života Masarykovy univerzity.',
        ]);

        $ppv = Venue::create([
            'name' => 'Koleje Pod Palackého vrchem (PPV)',
            'slug' => 'koleje-ppv',
            'address' => 'Kolejní 2',
            'city' => 'Brno',
            'latitude' => 49.2291,
            'longitude' => 16.5750,
            'description' => 'Největší areál kolejí VUT v Brně.',
        ]);

        $scala = Venue::create([
            'name' => 'Univerzitní kino Scala',
            'slug' => 'univerzitni-kino-scala',
            'address' => 'Moravské nám. 3',
            'city' => 'Brno',
            'latitude' => 49.1983,
            'longitude' => 16.6074,
            'description' => 'Studentské a artové kino MUNI na Moravském náměstí.',
        ]);

        $luzanky = Venue::create([
            'name' => 'Park Lužánky',
            'slug' => 'park-luzanky',
            'address' => 'Lidická',
            'city' => 'Brno',
            'latitude' => 49.2065,
            'longitude' => 16.6110,
            'description' => 'Nejstarší veřejný park v ČR pro pikniky a outdoor aktivity.',
        ]);

        // 2. Categories
        $catParty = Category::create([
            'name' => 'Párty & Kluby',
            'slug' => 'party-kluby',
            'description' => 'Večírky, semestrální otvíráky, klubové noci',
            'icon' => 'beer',
            'color' => 'primary',
            'sort_order' => 1,
        ]);

        $catSport = Category::create([
            'name' => 'Turnaje & Sport',
            'slug' => 'turnaje-sport',
            'description' => 'Beerpong, volejbal, šachy, fotbálky',
            'icon' => 'trophy',
            'color' => 'accent',
            'sort_order' => 2,
        ]);

        $catQuiz = Category::create([
            'name' => 'Kvízy & Hry',
            'slug' => 'kvizy-hry',
            'description' => 'Hospodské kvízy, deskovky, LAN party',
            'icon' => 'help-circle',
            'color' => 'info',
            'sort_order' => 3,
        ]);

        $catEdu = Category::create([
            'name' => 'Přednášky & Kariéra',
            'slug' => 'prednasky-kariera',
            'description' => 'Workshopy, diskuze se zajímavými hosty, veletrhy',
            'icon' => 'graduation-cap',
            'color' => 'secondary',
            'sort_order' => 4,
        ]);

        $catCulture = Category::create([
            'name' => 'Kultura & Divadlo',
            'slug' => 'kultura-divadlo',
            'description' => 'Impro show, koncerty, promítání',
            'icon' => 'palette',
            'color' => 'neutral',
            'sort_order' => 5,
        ]);

        // 3. Organizations (Spolky)
        $munie = Organization::create([
            'name' => 'Masarykova studentská unie (MUNIE)',
            'slug' => 'munie',
            'university' => 'MUNI',
            'description' => 'Hlavní zastřešující studentská organizace na Masarykově univerzitě.',
            'website_url' => 'https://munie.cz',
            'instagram_handle' => 'munie_cz',
            'facebook_url' => 'https://facebook.com/munie.cz',
            'contact_email' => 'info@munie.cz',
            'is_verified' => true,
        ]);

        $best = Organization::create([
            'name' => 'BEST Brno',
            'slug' => 'best-brno',
            'university' => 'VUT',
            'faculty' => 'Všechny fakulty VUT',
            'description' => 'Board of European Students of Technology při VUT v Brně.',
            'website_url' => 'https://best.vutbr.cz',
            'instagram_handle' => 'bestbrno',
            'contact_email' => 'brno@best-eu.org',
            'is_verified' => true,
        ]);

        $esn = Organization::create([
            'name' => 'ESN MUNI Brno',
            'slug' => 'esn-muni-brno',
            'university' => 'MUNI',
            'description' => 'Erasmus Student Network – organizujeme mezinárodní akce a integrujeme zahraniční studenty.',
            'website_url' => 'https://esnmuni.cz',
            'instagram_handle' => 'esn_muni',
            'is_verified' => true,
        ]);

        $mendeluUnie = Organization::create([
            'name' => 'Unie studentů MENDELU',
            'slug' => 'unie-studentu-mendelu',
            'university' => 'MENDELU',
            'description' => 'Spolek sdružující studenty Mendelovy univerzity v Brně.',
            'instagram_handle' => 'us_mendelu',
            'is_verified' => true,
        ]);

        // 4. Users (Superadmin & Club Admins)
        User::create([
            'name' => 'Super Administrátor',
            'email' => 'admin@brnoakce.cz',
            'password' => Hash::make('admin123'),
            'role' => 'superadmin',
            'organization_id' => null,
        ]);

        User::create([
            'name' => 'Zástupce MUNIE',
            'email' => 'munie@brnoakce.cz',
            'password' => Hash::make('spolek123'),
            'role' => 'club_admin',
            'organization_id' => $munie->id,
        ]);

        User::create([
            'name' => 'Zástupce BEST Brno',
            'email' => 'best@brnoakce.cz',
            'password' => Hash::make('spolek123'),
            'role' => 'club_admin',
            'organization_id' => $best->id,
        ]);

        // 5. Events
        $now = Carbon::now();

        // Akce 1: Dnes večer
        Event::create([
            'organization_id' => $munie->id,
            'venue_id' => $artbar->id,
            'category_id' => $catQuiz->id,
            'title' => 'Velký studentský hospodský kvíz o ceny',
            'slug' => 'velky-studentsky-hospodsky-kviz-o-ceny',
            'short_description' => 'Prověř své znalosti popkultury, brněnských kolejních mýtů a vědy. Týmy 3–6 lidí.',
            'description' => "Tradiční semestrální kvíz Masarykovy studentské unie. Registrace týmů na místě od 18:30, začátek přesně v 19:00.\n\nCeny pro první 3 týmy:\n1. Místo: Sud piva + deskovka\n2. Místo: Poukaz do baru na 500 Kč\n3. Místo: Diplom a láhev studentského elixíru.",
            'cover_image' => 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $now->copy()->setTime(19, 0),
            'ends_at' => $now->copy()->setTime(22, 30),
            'price_type' => 'paid',
            'price_amount' => 50,
            'status' => 'published',
            'is_featured' => true,
            'views_count' => 142,
        ]);

        // Akce 2: Zítra
        Event::create([
            'organization_id' => $esn->id,
            'venue_id' => $vinarska->id,
            'category_id' => $catCulture->id,
            'title' => 'International Meet & Board Games Night',
            'slug' => 'international-meet-and-board-games-night',
            'short_description' => 'Seznam se se zahraničními studenty z celého světa u deskových her a drinku.',
            'description' => 'English friendly večer plný her jako Catan, Bang, Codenames i Secret Hitler. Vstup je zcela zdarma, stačí přijít.',
            'cover_image' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $now->copy()->addDay()->setTime(18, 0),
            'ends_at' => $now->copy()->addDay()->setTime(23, 0),
            'price_type' => 'free',
            'status' => 'published',
            'is_featured' => false,
            'views_count' => 88,
        ]);

        // Akce 3: Tento pátek / víkend
        $nextFriday = $now->copy()->next(Carbon::FRIDAY);
        Event::create([
            'organization_id' => $munie->id,
            'venue_id' => $fleda->id,
            'category_id' => $catParty->id,
            'title' => 'Semestrální Mecheche na Flédě 2026',
            'slug' => 'semestralni-mecheche-na-flede-2026',
            'short_description' => 'Největší mezifakultní párty podzimu. Dvě stage, akční drinky, DJs z celé ČR.',
            'description' => "Hlavní stage: Electro, House & Pop anthems.\nDruhá stage: DNB & Bass music.\n\nPředprodej spuštěn přes GoOut za zvýhodněných 150 Kč, na místě 220 Kč. Zákaz vstupu pod 18 let bez ISIC/OP.",
            'cover_image' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $nextFriday->copy()->setTime(21, 0),
            'ends_at' => $nextFriday->copy()->addDay()->setTime(5, 0),
            'price_type' => 'paid',
            'price_amount' => 150,
            'ticket_url' => 'https://goout.net',
            'facebook_event_url' => 'https://facebook.com/events/12345678',
            'status' => 'published',
            'is_featured' => true,
            'views_count' => 520,
        ]);

        // Akce 4: Sobota víkend – Sport
        $nextSaturday = $now->copy()->next(Carbon::SATURDAY);
        Event::create([
            'organization_id' => $best->id,
            'venue_id' => $ppv->id,
            'category_id' => $catSport->id,
            'title' => 'Meziuniverzitní Beerpong Cup Brno 2026',
            'slug' => 'meziuniverzitni-beerpong-cup-brno-2026',
            'short_description' => '32 dvoučlenných týmů, oficiální SPPDF stoly, trofej šampionů Brna a věčná sláva.',
            'description' => "Turnaj v beerpongu pro studenty MUNI, VUT i MENDELU.\n\nPravidla: Standardní 10 kelímků, pivo Radegast / nealko Birell volitelně.\nStartovné zahrnuje turnajové pivo pro celý tým na všechny zápasy ve skupině i play-off.",
            'cover_image' => 'https://images.unsplash.com/photo-1543007630-9710e4a00a20?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $nextSaturday->copy()->setTime(15, 0),
            'ends_at' => $nextSaturday->copy()->setTime(21, 0),
            'price_type' => 'paid',
            'price_amount' => 200,
            'status' => 'published',
            'is_featured' => true,
            'views_count' => 310,
        ]);

        // Akce 5: Příští týden – Přednáška
        $nextTuesday = $now->copy()->addWeek()->startOfWeek()->addDay();
        Event::create([
            'organization_id' => $best->id,
            'venue_id' => $scala->id,
            'category_id' => $catEdu->id,
            'title' => 'TechTalk: Jak postavit AI startup během studia v Brně',
            'slug' => 'techtalk-jak-postavit-ai-startup-behem-studia',
            'short_description' => 'Tři zakladatelé brněnských technologických firem sdílí své fuckupy a tipy pro začínající vývojáře.',
            'description' => "Hosté:\n- Petr Novák (zakladatel AI nástroje pro nemocnice)\n- Jana Dvořáková (CTO v brněnském fin-techu)\n\nNásleduje networking s pivem a pizzou zdarma.",
            'cover_image' => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $nextTuesday->copy()->setTime(17, 30),
            'ends_at' => $nextTuesday->copy()->setTime(19, 30),
            'price_type' => 'free',
            'status' => 'published',
            'is_featured' => false,
            'views_count' => 195,
        ]);

        // Akce 6 & 7: PENDING REVIEW (Scrapované akce pro otestování schvalovací fronty!)
        Event::create([
            'organization_id' => $mendeluUnie->id,
            'venue_id' => $luzanky->id,
            'category_id' => $catCulture->id,
            'title' => '[Scraped IG] Podzimní studentský swap kytek a oblečení',
            'slug' => 'podzimni-studentsky-swap-kytek-a-obleceni',
            'short_description' => 'Zachyceno z Instagramu @us_mendelu. Přines co nenosíš, odnes si nový kousek nebo řízek monstery.',
            'description' => 'Automaticky vyextrahováno z letáku na Instagram Stories dne ' . Carbon::now()->format('d.m.Y') . '. Vstup zdarma.',
            'cover_image' => 'https://images.unsplash.com/photo-1528740561666-dc2479dc08ab?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $now->copy()->addDays(5)->setTime(14, 0),
            'ends_at' => $now->copy()->addDays(5)->setTime(18, 0),
            'price_type' => 'free',
            'source_type' => 'scraper_instagram',
            'source_url' => 'https://instagram.com/p/example_post',
            'status' => 'pending_review',
            'is_featured' => false,
        ]);

        Event::create([
            'organization_id' => $munie->id,
            'venue_id' => $vinarska->id,
            'category_id' => $catParty->id,
            'title' => '[Scraped FB] Kolejní grilovačka & chillout na Vinařské',
            'slug' => 'kolejni-grilovacka-and-chillout-na-vinarske',
            'short_description' => 'Zachyceno z Facebook Events. Oheň, hudba, deky a vychlazené drinky.',
            'description' => 'Položeno do schvalovací fronty robotem ze stránky Facebook MUNIE.',
            'cover_image' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80',
            'starts_at' => $now->copy()->addDays(6)->setTime(16, 0),
            'ends_at' => $now->copy()->addDays(6)->setTime(22, 0),
            'price_type' => 'free',
            'source_type' => 'scraper_facebook',
            'source_url' => 'https://facebook.com/events/example_fb_event',
            'status' => 'pending_review',
            'is_featured' => false,
        ]);
    }
}
