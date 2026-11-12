<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModerationAndScraperFilterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_soft_deletes_and_rejected_events_persist_in_database(): void
    {
        $org = Organization::first();
        $cat = Category::first();

        $event = Event::create([
            'title' => 'Akce k zamítnutí',
            'slug' => 'akce-k-zamitnuti-' . uniqid(),
            'starts_at' => now()->addDays(3),
            'organization_id' => $org->id,
            'category_id' => $cat->id,
            'status' => 'pending_review',
            'source_url' => 'https://example.com/events/to-reject',
        ]);

        // Zamítnutí
        $event->update(['status' => 'rejected']);
        $this->assertEquals('rejected', $event->fresh()->status);
        $this->assertTrue(Event::rejected()->where('id', $event->id)->exists());

        // Soft delete
        $event->delete();
        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertTrue(Event::withTrashed()->where('id', $event->id)->exists());
    }

    public function test_scraper_deduplication_respects_soft_deleted_and_rejected_events(): void
    {
        $org = Organization::first();
        $cat = Category::first();

        $uniqueUrl = 'https://example.com/events/test-scraper-denied-' . uniqid();

        $event = Event::create([
            'title' => 'Zamítnutý článek ze scraperu',
            'slug' => 'zamitnuty-clanek-' . uniqid(),
            'starts_at' => now()->addDays(5),
            'organization_id' => $org->id,
            'category_id' => $cat->id,
            'status' => 'rejected',
            'source_url' => $uniqueUrl,
        ]);

        $this->assertTrue(
            Event::withTrashed()->where('source_url', $uniqueUrl)->exists(),
            'Scraper deduplication query must find rejected or soft-deleted items to avoid rescraping'
        );

        $event->delete(); // Smazáno do koše

        $this->assertTrue(
            Event::withTrashed()->where('source_url', $uniqueUrl)->exists(),
            'Even when soft deleted, withTrashed must detect it'
        );
    }

    public function test_irrelevant_articles_are_identified(): void
    {
        $command = new \App\Console\Commands\ScrapeEventsCommand();

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('isIrrelevantArticle');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($command, 'Jan Černocký z FIT VUT získal Národní cenu vlády Česká hlava', ''));
        $this->assertTrue($method->invoke($command, 'Výukové změny v ZS 2026: DOD', ''));
        $this->assertTrue($method->invoke($command, 'Přehled vybraných zkoušek po ZIMNÍM semestru', ''));
        $this->assertTrue($method->invoke($command, 'Co se stane s dopravou? Odpověď hledá výzkum z FIT VUT', ''));
        // MUNI specifické nerelevantní položky (CŽV, jmenování, ZŠ/SŠ učitelé, NLZP)
        $this->assertTrue($method->invoke($command, 'Trenér tanečních technik II. třídy', 'KURZ CŽV'));
        $this->assertTrue($method->invoke($command, 'Geovědy pro environmentální vzdělávání', 'Kurz je určen pro učitele přírodopisu na ZŠ a SŠ'));
        $this->assertTrue($method->invoke($command, 'Řízení ke jmenování profesorkou doc. Mgr. Novákové', ''));
        $this->assertTrue($method->invoke($command, 'Akutní stavy v anestezii dětí', 'Pro NLZP'));
        $this->assertTrue($method->invoke($command, 'Zasedání akademického senátu MU', ''));

        // Skutečné akce nesmí být označeny jako irelevantní
        $this->assertFalse($method->invoke($command, 'Meziuniverzitní Beerpong Cup na PPV', 'Turnaj studentů'));
        $this->assertFalse($method->invoke($command, 'Semestrální Mecheche na Flédě', 'Tradiční uvítací párty'));
        $this->assertFalse($method->invoke($command, 'Kreativní workshop malování květináčů', 'Dopřej si odpočinek'));
        $this->assertFalse($method->invoke($command, 'Křehká svoboda 2026', 'Diskuze Masarykovy univerzity k 17. listopadu'));
    }
}
