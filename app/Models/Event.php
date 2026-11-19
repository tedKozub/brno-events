<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'venue_id',
        'category_id',
        'title',
        'slug',
        'short_description',
        'description',
        'cover_image',
        'cover_image_type',
        'original_image_url',
        'starts_at',
        'ends_at',
        'is_all_day',
        'location_name',
        'price_type',
        'price_amount',
        'price_currency',
        'ticket_url',
        'facebook_event_url',
        'instagram_post_url',
        'source_type',
        'source_url',
        'status',
        'is_featured',
        'views_count',
        'embedding',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean',
        'is_featured' => 'boolean',
        'price_amount' => 'decimal:2',
        'views_count' => 'integer',
        'embedding' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $baseSlug = Str::slug($event->title);
                $event->slug = $baseSlug . '-' . Str::random(6);
            }
        });

        static::saved(function ($event) {
            if (empty($event->embedding) && app()->environment() !== 'testing') {
                try {
                    app(\App\Services\EmbeddingService::class)->updateEventEmbedding($event);
                } catch (\Throwable $e) {}
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('starts_at', '>=', Carbon::today())
              ->orWhere('ends_at', '>=', Carbon::now());
        });
    }

    public function getLocationDisplayAttribute(): string
    {
        $loc = trim($this->location_name ?? '');

        if ($this->venue) {
            $baseVenue = $this->venue->name . ($this->venue->address ? ', ' . $this->venue->address : '');

            // Pokud location_name obsahuje specifickou učebnu, místnost, budovu či sál
            if ($loc !== '' && !str_starts_with(mb_strtolower($loc), mb_strtolower($this->venue->name))) {
                if (preg_match('/(učebn|ucebn|místnost|mistnost|budov|sál|sal|aula|posluchárn|poslucharn|q\d+|lab)/ui', $loc)) {
                    $cleanLoc = preg_replace('/,?\s*brno\s*$/ui', '', $loc);
                    if (str_contains(mb_strtolower($cleanLoc), mb_strtolower($this->venue->name))) {
                        return $cleanLoc;
                    }
                    return "{$cleanLoc} ({$this->venue->name})";
                }
            }

            return $baseVenue;
        }

        return $loc ?: 'Brno centrum';
    }

    public function getIsMultiDayAttribute(): bool
    {
        if (!$this->starts_at || !$this->ends_at) {
            return false;
        }
        return $this->ends_at->isAfter($this->starts_at->copy()->endOfDay());
    }

    public function getIsLongTermAttribute(): bool
    {
        if (!$this->starts_at || !$this->ends_at) {
            return false;
        }
        return abs($this->ends_at->diffInDays($this->starts_at)) >= 4;
    }

    public function getIsOngoingAttribute(): bool
    {
        if (!$this->starts_at || !$this->ends_at) {
            return false;
        }
        $now = Carbon::now();
        return $this->starts_at->isPast() && $this->ends_at->gte($now);
    }

    public function getFormattedDateRangeAttribute(): string
    {
        if (!$this->starts_at) {
            return '';
        }

        if ($this->is_ongoing && $this->ends_at) {
            return 'Probíhá • do ' . $this->ends_at->format('j. n.');
        }

        if ($this->is_multi_day && $this->ends_at) {
            if ($this->starts_at->month === $this->ends_at->month && $this->starts_at->year === $this->ends_at->year) {
                return $this->starts_at->format('j.') . ' – ' . $this->ends_at->format('j. n.');
            }
            return $this->starts_at->format('j. n.') . ' – ' . $this->ends_at->format('j. n.');
        }

        $days = ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'];
        $dayName = $days[$this->starts_at->dayOfWeek] ?? '';
        return trim($dayName . ' ' . $this->starts_at->format('j. n. H:i'));
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->price_type === 'free') {
            return 'Zdarma';
        }

        if ($this->price_type === 'voluntary') {
            return 'Dobrovolné';
        }

        if ($this->price_amount) {
            return number_format($this->price_amount, 0, ',', ' ') . ' ' . $this->price_currency;
        }

        return 'Placené';
    }

    public function getCoverImageUrlAttribute(): string
    {
        $stockUrl = match($this->category?->slug) {
            'party-kluby' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=75',
            'prednasky-kariera', 'akademie-vzdelavani' => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=800&q=75',
            'kvizy-hry' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=800&q=75',
            'turnaje-sport', 'sport-outdoor' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=800&q=75',
            'kultura-umeni' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=75',
            default => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=800&q=75',
        };

        if (empty($this->cover_image)) {
            return $stockUrl;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        $cleanPath = ltrim(preg_replace('#^/?storage/#', '', $this->cover_image), '/');
        if (!Storage::disk('public')->exists($cleanPath)) {
            return $stockUrl;
        }

        return '/storage/' . $cleanPath;
    }

    public function getEffectiveCoverImageTypeAttribute(): string
    {
        if (empty($this->cover_image)) {
            return 'stock';
        }

        if (!str_starts_with($this->cover_image, 'http://') && !str_starts_with($this->cover_image, 'https://')) {
            $cleanPath = ltrim(preg_replace('#^/?storage/#', '', $this->cover_image), '/');
            if (!Storage::disk('public')->exists($cleanPath)) {
                return 'stock';
            }
        }

        if (!empty($this->cover_image_type)) {
            return $this->cover_image_type;
        }

        // If stored locally on disk, determine type dynamically by aspect ratio
        if (!str_starts_with($this->cover_image, 'http://') && !str_starts_with($this->cover_image, 'https://')) {
            $cleanPath = ltrim(preg_replace('#^/?storage/#', '', $this->cover_image), '/');
            $fullPath = Storage::disk('public')->path($cleanPath);
            if (file_exists($fullPath)) {
                $dims = @getimagesize($fullPath);
                if ($dims && $dims[0] > 0 && $dims[1] > 0) {
                    $w = $dims[0];
                    $h = $dims[1];
                    if ($h > 1.15 * $w) {
                        return 'poster';
                    }
                    if ($w >= 1.2 * $h) {
                        return 'banner';
                    }
                    return 'article';
                }
            }
        }

        $lower = strtolower($this->cover_image);
        if (str_contains($lower, 'poster') || str_contains($lower, 'plakat')) {
            return 'poster';
        }

        if (str_contains($lower, 'banner') || str_contains($lower, 'hero') || str_contains($lower, 'header') || str_contains($lower, 'fb_') || str_contains($lower, 'ig_')) {
            return 'banner';
        }

        return 'article';
    }

    public function getCoverImageTypeDisplayAttribute(): string
    {
        return match($this->effective_cover_image_type) {
            'poster', 'banner' => 'Plakát akce',
            'article' => 'Obrázek z článku',
            default => 'Ilustrační foto',
        };
    }

    public function isPoster(): bool
    {
        return $this->effective_cover_image_type === 'poster';
    }

    public function isStockImage(): bool
    {
        return $this->effective_cover_image_type === 'stock';
    }

    public function getMapCoordinatesAttribute(): ?array
    {
        if ($this->venue && $this->venue->latitude && $this->venue->longitude) {
            return [
                'lat' => (float) $this->venue->latitude,
                'lng' => (float) $this->venue->longitude,
                'title' => $this->venue->name,
                'address' => $this->venue->address ? $this->venue->address . ', ' . $this->venue->city : ($this->venue->city ?: 'Brno'),
                'is_exact' => true,
            ];
        }

        // Prioritně čteme ze samotného pole location_name (nikdy nenecháme obecný text z popisu přebít zadané místo!)
        $locText = mb_strtolower(trim($this->location_name ?? ''));
        $fullText = mb_strtolower(trim($this->title . ' ' . $this->location_name . ' ' . ($this->description ?? '')));
        $text = $fullText;

        $matchLocation = function(array $keywords) use ($locText, $fullText) {
            foreach ($keywords as $kw) {
                if ($locText !== '' && str_contains($locText, $kw)) {
                    return true;
                }
            }
            if ($locText === '' || in_array($locText, ['brno', 'brno centrum', 'brno - střed', 'univerzita'])) {
                foreach ($keywords as $kw) {
                    if (str_contains($fullText, $kw)) {
                        return true;
                    }
                }
            }
            return false;
        };

        // 1. VUT fakulty a areály
        if ($matchLocation(['fekt', 'technická 12', 'technicka 12', 'aula profesora braunera'])) {
            return [
                'lat' => 49.2263,
                'lng' => 16.5742,
                'title' => 'FEKT VUT (Technická 12)',
                'address' => 'Technická 12, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['fsi', 'technická 2', 'technicka 2', 'technická 2896'])) {
            return [
                'lat' => 49.2241,
                'lng' => 16.5786,
                'title' => 'FSI VUT (Technická 2)',
                'address' => 'Technická 2896/2, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['božetěchov', 'bozetechov', 'kachničk']) || (str_contains($locText, 'fit vut') && !str_contains($locText, 'fekt') && !str_contains($locText, 'fsi'))) {
            return [
                'lat' => 49.2268,
                'lng' => 16.5969,
                'title' => 'FIT VUT (Božetěchova 1/2)',
                'address' => 'Božetěchova 1/2, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        // 2. MENDELU areál a učebny (Zemědělská 1)
        if ($matchLocation(['mendelu', 'zemědělsk', 'zemedelsk', 'q48', 'budova m', 'budova q', 'budova e', 'budova f', 'design lab'])) {
            return [
                'lat' => 49.2104,
                'lng' => 16.6166,
                'title' => 'Areál MENDELU (Zemědělská 1)',
                'address' => 'Zemědělská 1, Brno – Černá Pole',
                'is_exact' => true,
            ];
        }

        // 3. Univerzitní kampus Bohunice (MUNI - Kamenice 5)
        if ($matchLocation(['kamenice', 'kampus bohunice', 'bohunick', 'kampus muni', 'b11/', 'simu', 'simulační centrum', 'fn bohunice'])) {
            return [
                'lat' => 49.1772,
                'lng' => 16.5684,
                'title' => 'Univerzitní kampus Bohunice (MUNI)',
                'address' => 'Kamenice 5, Brno – Bohunice',
                'is_exact' => true,
            ];
        }

        // 4. MUNI jednotlivé fakulty
        if ($matchLocation(['botanická 68', 'botanické 68', 'fi muni', 'fakulta informatiky'])) {
            return [
                'lat' => 49.2099,
                'lng' => 16.6041,
                'title' => 'Fakulta informatiky MUNI (FI MUNI)',
                'address' => 'Botanická 68a, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['kotlářsk', 'kotlarske', 'přírodovědecká fakulta', 'prirodovedecka fakulta', 'přf muni', 'prf muni'])) {
            return [
                'lat' => 49.2052,
                'lng' => 16.5982,
                'title' => 'Přírodovědecká fakulta MUNI (Kotlářská)',
                'address' => 'Kotlářská 2, Brno – Veveří',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['lipov', 'lipove', 'econ.muni', 'esf muni', 'ekonomicko-správní'])) {
            return [
                'lat' => 49.1939,
                'lng' => 16.5786,
                'title' => 'Ekonomicko-správní fakulta MUNI (ESF)',
                'address' => 'Lipová 41a, Brno – Pisárky',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['joštov', 'jostove', 'fss muni', 'fakulta sociálních studií'])) {
            return [
                'lat' => 49.1973,
                'lng' => 16.6047,
                'title' => 'Fakulta sociálních studií MUNI (FSS)',
                'address' => 'Joštova 10, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['veveří 70', 'veveri 70', 'právnická fakulta', 'pravnicka fakulta', 'law muni'])) {
            return [
                'lat' => 49.2045,
                'lng' => 16.5966,
                'title' => 'Právnická fakulta MUNI',
                'address' => 'Veveří 70, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['arna novák', 'arne novák', 'arna novaka', 'arne novaka', 'ff mu', 'ff muni', 'filozofická fakulta'])) {
            return [
                'lat' => 49.2003,
                'lng' => 16.5996,
                'title' => 'Filozofická fakulta MUNI',
                'address' => 'Arna Nováka 1, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['poříčí', 'porici', 'pdf muni', 'pedagogická fakulta', 'pedagogicka fakulta'])) {
            return [
                'lat' => 49.1872,
                'lng' => 16.5960,
                'title' => 'Pedagogická fakulta MUNI (Poříčí)',
                'address' => 'Poříčí 7/9, Brno – Štýřice',
                'is_exact' => true,
            ];
        }

        // 5. Univerzita obrany (Kasárna Černá Pole / Šumavská)
        if ($matchLocation(['kasárna', 'kasarna', 'černá pole', 'cerna pole', 'šumavská', 'sumavska', 'univerzita obrany', 'unob'])) {
            return [
                'lat' => 49.2132,
                'lng' => 16.6171,
                'title' => 'Univerzita obrany (Kasárna Černá Pole / Šumavská)',
                'address' => 'Tř. Generála Píky 229/7, Brno',
                'is_exact' => true,
            ];
        }

        // 6. VETUNI (Palackého třída)
        if ($matchLocation(['vetuni', 'palackého tř', 'palackeho tr'])) {
            return [
                'lat' => 49.2183,
                'lng' => 16.5975,
                'title' => 'Kampus VETUNI Palackého',
                'address' => 'Palackého tř. 1946/1, Brno',
                'is_exact' => true,
            ];
        }

        // 7. Známé kluby, kavárny a sály
        if ($matchLocation(['fléda', 'fleda'])) {
            return [
                'lat' => 49.2096,
                'lng' => 16.6033,
                'title' => 'Fléda Club',
                'address' => 'Štefánikova 24, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['artbar', 'druhý pád', 'druhy pad'])) {
            return [
                'lat' => 49.2062,
                'lng' => 16.6042,
                'title' => 'ArtBar Druhý Pád',
                'address' => 'Štefánikova 1, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['koma lounge', 'koma bar', 'koma'])) {
            return [
                'lat' => 49.2040,
                'lng' => 16.5985,
                'title' => 'Koma Lounge & Bar',
                'address' => 'Kounicova 42, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['maison'])) {
            return [
                'lat' => 49.1962,
                'lng' => 16.6089,
                'title' => 'Maison Club & Lounge',
                'address' => 'Běhounská 9, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['babylon'])) {
            return [
                'lat' => 49.2018,
                'lng' => 16.6006,
                'title' => 'KC Babylon',
                'address' => 'Kounicova 20, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['skautský institut', 'skautskem institutu', 'skautsky institut'])) {
            return [
                'lat' => 49.199656,
                'lng' => 16.605770,
                'title' => 'Skautský institut v Brně',
                'address' => 'Moravské náměstí 12, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['morgal'])) {
            return [
                'lat' => 49.1979,
                'lng' => 16.6079,
                'title' => 'Café Morgal',
                'address' => 'Moravské náměstí 1a, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['kabinet múz', 'kabinet muz'])) {
            return [
                'lat' => 49.1965,
                'lng' => 16.6111,
                'title' => 'Kabinet MÚZ',
                'address' => 'Sukova 4, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['music lab', 'musiclab'])) {
            return [
                'lat' => 49.1981,
                'lng' => 16.6060,
                'title' => 'Music Lab',
                'address' => 'Opletalova 1, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['trojk', 'kavárna trojka', 'kavarna trojka'])) {
            return [
                'lat' => 49.1938,
                'lng' => 16.6063,
                'title' => 'Kavárna Trojka',
                'address' => 'Dominikánská 9, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['šelepk', 'selepk'])) {
            return [
                'lat' => 49.2139,
                'lng' => 16.5919,
                'title' => 'Klub Šelepka',
                'address' => 'Šelepova 1, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['sono'])) {
            return [
                'lat' => 49.2096,
                'lng' => 16.5898,
                'title' => 'Sono Music Club',
                'address' => 'Veveří 113, Brno – Žabovřesky',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['perpetuum'])) {
            return [
                'lat' => 49.1963,
                'lng' => 16.6118,
                'title' => 'Perpetuum Club',
                'address' => 'Rooseveltova 9, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['otevřená zahrada', 'otevrena zahrada'])) {
            return [
                'lat' => 49.1956,
                'lng' => 16.5986,
                'title' => 'Otevřená zahrada',
                'address' => 'Údolní 33, Brno – střed',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['impact hub'])) {
            return [
                'lat' => 49.1906,
                'lng' => 16.6198,
                'title' => 'Impact Hub Brno',
                'address' => 'Cyrilská 7, Brno – Trnitá',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['lužánk', 'luzank'])) {
            return [
                'lat' => 49.2065,
                'lng' => 16.6110,
                'title' => 'Park Lužánky',
                'address' => 'Pionýrská 280/1, Brno – Černá Pole',
                'is_exact' => true,
            ];
        }

        if ($matchLocation(['nová radnice', 'nova radnice', 'sněmovní sál', 'snemovni sal'])) {
            return [
                'lat' => 49.1937,
                'lng' => 16.6066,
                'title' => 'Nová radnice',
                'address' => 'Dominikánské náměstí 1, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'exit club') || str_contains($text, 'exit')) {
            return [
                'lat' => 49.1936,
                'lng' => 16.6074,
                'title' => 'EXIT Club Brno',
                'address' => 'Dominikánské náměstí 456/2, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'favál') || str_contains($text, 'faval')) {
            return [
                'lat' => 49.1864,
                'lng' => 16.5819,
                'title' => 'Favál Music Circus',
                'address' => 'Křížkovského 416/22, Brno – Pisárky',
                'is_exact' => true,
            ];
        }

        // 1.8 Brněnské ulice a náměstí
        if (str_contains($text, 'kounicova')) {
            return [
                'lat' => 49.2025,
                'lng' => 16.6000,
                'title' => 'Kounicova',
                'address' => 'Kounicova, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'moravské náměstí') || str_contains($text, 'moravskem namesti')) {
            return [
                'lat' => 49.1985,
                'lng' => 16.6078,
                'title' => 'Moravské náměstí',
                'address' => 'Moravské náměstí, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'nová radnice') || str_contains($text, 'nova radnice') || str_contains($text, 'sněmovní sál') || str_contains($text, 'snemovni sal')) {
            return [
                'lat' => 49.1937,
                'lng' => 16.6066,
                'title' => 'Nová radnice',
                'address' => 'Dominikánské náměstí 1, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'dominikánsk') || str_contains($text, 'dominikansk')) {
            return [
                'lat' => 49.1937,
                'lng' => 16.6066,
                'title' => 'Dominikánská / Dominikánské nám.',
                'address' => 'Dominikánská, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'zelný trh') || str_contains($text, 'zelnem trhu')) {
            return [
                'lat' => 49.1925,
                'lng' => 16.6090,
                'title' => 'Zelný trh',
                'address' => 'Zelný trh, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'štefánikov') || str_contains($text, 'stefanikov')) {
            return [
                'lat' => 49.2096,
                'lng' => 16.6033,
                'title' => 'Štefánikova',
                'address' => 'Štefánikova, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'údolní') || str_contains($text, 'udolni')) {
            return [
                'lat' => 49.1956,
                'lng' => 16.5986,
                'title' => 'Údolní',
                'address' => 'Údolní, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'pekařsk') || str_contains($text, 'pekarsk')) {
            return [
                'lat' => 49.1915,
                'lng' => 16.5992,
                'title' => 'Pekařská',
                'address' => 'Pekařská, Brno – Staré Brno',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'lidick')) {
            return [
                'lat' => 49.2050,
                'lng' => 16.6080,
                'title' => 'Lidická',
                'address' => 'Lidická, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'sukova')) {
            return [
                'lat' => 49.1965,
                'lng' => 16.6111,
                'title' => 'Sukova',
                'address' => 'Sukova, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'rooseveltov')) {
            return [
                'lat' => 49.1963,
                'lng' => 16.6118,
                'title' => 'Rooseveltova',
                'address' => 'Rooseveltova, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'purkyňov') || str_contains($text, 'kolejní') || str_contains($text, 'palackého vrch')) {
            return [
                'lat' => 49.2285,
                'lng' => 16.5750,
                'title' => 'Pod Palackého vrchem (VUT kolejní areál)',
                'address' => 'Kolejní 2, Brno – Medlánky',
                'is_exact' => true,
            ];
        }

        // 2. Fallback pro specifické custom location_name (pokud není přiřazeno známé venue)
        $hasCustomLoc = !empty($this->location_name) && !in_array(mb_strtolower(trim($this->location_name)), ['brno', 'brno centrum', 'brno - střed']);

        if ($hasCustomLoc) {
            $loc = trim($this->location_name);
            return [
                'lat' => 49.1951,
                'lng' => 16.6068,
                'title' => $loc,
                'address' => str_contains(mb_strtolower($loc), 'brno') ? $loc : ($loc . ', Brno'),
                'is_exact' => false,
            ];
        }

        // 3. Výchozí fallback pro město ("Brno centrum" – Náměstí Svobody)
        return [
            'lat' => 49.1951,
            'lng' => 16.6068,
            'title' => 'Brno centrum',
            'address' => 'Brno centrum',
            'is_exact' => false,
        ];
    }

    /**
     * Identifies missing or low-quality data attributes on this event.
     *
     * @return array<string, string> Key = field name, Value = human description
     */
    public function getMissingFieldsAttribute(): array
    {
        $missing = [];

        if (empty($this->cover_image)) {
            $missing['cover_image'] = 'Chybí plakát / banner';
        }

        if (empty($this->venue_id) && empty($this->location_name)) {
            $missing['venue'] = 'Chybí místo konání';
        } elseif (empty($this->venue_id)) {
            $missing['venue_pin'] = 'Místo nemá přesný GPS špendlík';
        }

        if (empty($this->description) || mb_strlen(trim($this->description)) < 30) {
            $missing['description'] = 'Chybí nebo je příliš krátký popis';
        }

        if (!$this->is_all_day && $this->starts_at && $this->starts_at->format('H:i') === '00:00') {
            $missing['time'] = 'Chybí čas začátku (00:00)';
        }

        if ($this->price_type === 'paid' && empty($this->price_amount) && empty($this->ticket_url)) {
            $missing['price'] = 'Placená akce bez částky či odkazu na vstupenky';
        }

        if (empty($this->organization_id)) {
            $missing['organization'] = 'Chybí pořádající organizace';
        }

        return $missing;
    }

    public function getHasMissingFieldsAttribute(): bool
    {
        return !empty($this->missing_fields);
    }

    public function scopeWithMissingData(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('cover_image')
              ->orWhereNull('venue_id')
              ->orWhereNull('description')
              ->orWhere('description', '')
              ->orWhereNull('organization_id');
        });
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->whereNotNull('cover_image')
            ->whereNotNull('venue_id')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->whereNotNull('organization_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\EventReport::class);
    }
}
