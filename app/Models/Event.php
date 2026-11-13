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
        if ($this->venue) {
            return $this->venue->name . ($this->venue->address ? ', ' . $this->venue->address : '');
        }

        return $this->location_name ?: 'Brno';
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
            'party-kluby' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=80',
            'prednasky-kariera', 'akademie-vzdelavani' => 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=1200&q=80',
            'kvizy-hry' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=1200&q=80',
            'turnaje-sport', 'sport-outdoor' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=80',
            'kultura-umeni' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=80',
            default => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=1200&q=80',
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

        $text = mb_strtolower($this->title . ' ' . $this->location_name . ' ' . ($this->description ?? ''));
        $uni = $this->organization?->university ?? 'OTHER';

        // 1. Specifické fakulty a areály
        if (str_contains($text, 'božetěchov') || str_contains($text, 'fit vut') || str_contains($text, 'bozetechov')) {
            return [
                'lat' => 49.2268,
                'lng' => 16.5969,
                'title' => 'FIT VUT (Božetěchova 1/2)',
                'address' => 'Božetěchova 1/2, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if ($uni === 'MENDELU' || str_contains($text, 'mendelu') || str_contains($text, 'zemědělsk')) {
            return [
                'lat' => 49.2104,
                'lng' => 16.6166,
                'title' => 'Areál MENDELU (Zemědělská 1)',
                'address' => 'Zemědělská 1, Brno – Černá Pole',
                'is_exact' => true,
            ];
        }

        // Univerzitní kampus Bohunice (MUNI - Přírodovědecká, Lékařská fakulta, FSpS, CEITEC)
        if (str_contains($text, 'kamenice') || str_contains($text, 'kampus bohunice') || str_contains($text, 'bohunick') || str_contains($text, 'kampus muni') || str_contains($text, 'univerzitní kampus') || str_contains($text, 'b11/') || str_contains($text, 'botanický seminář') || str_contains($text, 'ústav botaniky')) {
            return [
                'lat' => 49.1772,
                'lng' => 16.5684,
                'title' => 'Univerzitní kampus Bohunice (MUNI)',
                'address' => 'Kamenice 5, Brno – Bohunice',
                'is_exact' => true,
            ];
        }

        // Fakulta informatiky MUNI (FI MUNI - Botanická 68a)
        if (str_contains($text, 'botanická 68') || str_contains($text, 'botanické 68') || str_contains($text, 'fakulta informatiky') || str_contains($text, 'fi muni')) {
            return [
                'lat' => 49.2099,
                'lng' => 16.6041,
                'title' => 'Fakulta informatiky MUNI (FI MUNI)',
                'address' => 'Botanická 68a, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        // Přírodovědecká fakulta MUNI (Areál Kotlářská a Botanická zahrada PřF)
        if (str_contains($text, 'kotlářsk') || str_contains($text, 'přírodovědecká fakulta') || str_contains($text, 'přf muni') || str_contains($text, 'botanická zahrada')) {
            return [
                'lat' => 49.2052,
                'lng' => 16.5982,
                'title' => 'Přírodovědecká fakulta MUNI (Kotlářská)',
                'address' => 'Kotlářská 2, Brno – Veveří',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'lipov') || str_contains($text, 'econ.muni') || str_contains($text, 'ekonomicko-správní')) {
            return [
                'lat' => 49.1939,
                'lng' => 16.5786,
                'title' => 'Ekonomicko-správní fakulta MUNI (ESF)',
                'address' => 'Lipová 41a, Brno – Pisárky',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'joštov') || str_contains($text, 'fss')) {
            return [
                'lat' => 49.1973,
                'lng' => 16.6047,
                'title' => 'Fakulta sociálních studií MUNI (FSS)',
                'address' => 'Joštova 10, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'veveří') || str_contains($text, 'právnick')) {
            return [
                'lat' => 49.2045,
                'lng' => 16.5966,
                'title' => 'Právnická fakulta MUNI',
                'address' => 'Veveří 70, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'arne novák') || str_contains($text, 'filozofick')) {
            return [
                'lat' => 49.2003,
                'lng' => 16.5996,
                'title' => 'Filozofická fakulta MUNI',
                'address' => 'Arne Nováka 1, Brno – střed',
                'is_exact' => true,
            ];
        }


        // 1.5 Známé brněnské kluby, kavárny, sály a parky
        if (str_contains($text, 'babylon')) {
            return [
                'lat' => 49.2018,
                'lng' => 16.6006,
                'title' => 'KC Babylon',
                'address' => 'Kounicova 20, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'koma lounge') || str_contains($text, 'koma')) {
            return [
                'lat' => 49.2040,
                'lng' => 16.5985,
                'title' => 'Koma Lounge & Bar',
                'address' => 'Kounicova 42, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'morgal')) {
            return [
                'lat' => 49.1979,
                'lng' => 16.6079,
                'title' => 'Café Morgal',
                'address' => 'Moravské náměstí 1a, Brno – střed',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'poříčí') || str_contains($text, 'pdf muni') || str_contains($text, 'pedagogick')) {
            return [
                'lat' => 49.1872,
                'lng' => 16.5960,
                'title' => 'Pedagogická fakulta MUNI (Poříčí)',
                'address' => 'Poříčí 7/9, Brno – Štýřice',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'fléda') || str_contains($text, 'fleda')) {
            return [
                'lat' => 49.2096,
                'lng' => 16.6033,
                'title' => 'Fléda Club',
                'address' => 'Štefánikova 24, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'artbar') || str_contains($text, 'druhý pád')) {
            return [
                'lat' => 49.2062,
                'lng' => 16.6042,
                'title' => 'ArtBar Druhý Pád',
                'address' => 'Štefánikova 1, Brno – Královo Pole',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'lužánk') || str_contains($text, 'luzank')) {
            return [
                'lat' => 49.2065,
                'lng' => 16.6110,
                'title' => 'Park Lužánky',
                'address' => 'Pionýrská 280/1, Brno – Černá Pole',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'sono')) {
            return [
                'lat' => 49.2096,
                'lng' => 16.5898,
                'title' => 'Sono Music Club',
                'address' => 'Veveří 113, Brno – Žabovřesky',
                'is_exact' => true,
            ];
        }

        if (str_contains($text, 'perpetuum')) {
            return [
                'lat' => 49.1963,
                'lng' => 16.6118,
                'title' => 'Perpetuum Club',
                'address' => 'Rooseveltova 9, Brno – střed',
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

        // 2. Univerzitní centrály / rektoráty
        if ($uni === 'MUNI') {
            return [
                'lat' => 49.1977,
                'lng' => 16.6043,
                'title' => 'Masarykova univerzita (Komenského nám.)',
                'address' => 'Komenského nám. 2, Brno – střed',
                'is_exact' => false,
            ];
        }

        if ($uni === 'VUT') {
            return [
                'lat' => 49.2023,
                'lng' => 16.6062,
                'title' => 'VUT v Brně (Antonínská)',
                'address' => 'Antonínská 1, Brno – střed',
                'is_exact' => false,
            ];
        }

        if ($uni === 'UNOB') {
            return [
                'lat' => 49.2114,
                'lng' => 16.5947,
                'title' => 'Univerzita obrany (Kasárna Černá Pole)',
                'address' => 'Kounicova 65, Brno – střed',
                'is_exact' => false,
            ];
        }

        if ($uni === 'VETUNI') {
            return [
                'lat' => 49.2173,
                'lng' => 16.5963,
                'title' => 'Veterinární univerzita Brno (Areál)',
                'address' => 'Palackého tř. 1946/1, Brno – Královo Pole',
                'is_exact' => false,
            ];
        }

        // 3. Centrum Brna
        return [
            'lat' => 49.1951,
            'lng' => 16.6068,
            'title' => 'Brno centrum',
            'address' => 'Náměstí Svobody, Brno',
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
