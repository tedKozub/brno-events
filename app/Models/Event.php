<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'venue_id',
        'category_id',
        'title',
        'slug',
        'short_description',
        'description',
        'cover_image',
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
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean',
        'is_featured' => 'boolean',
        'price_amount' => 'decimal:2',
        'views_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $baseSlug = Str::slug($event->title);
                $event->slug = $baseSlug . '-' . Str::random(6);
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

        if (str_contains($text, 'botanick') || str_contains($text, 'fakulta informatiky') || str_contains($text, 'fi muni')) {
            return [
                'lat' => 49.2099,
                'lng' => 16.6041,
                'title' => 'Fakulta informatiky MUNI (FI MUNI)',
                'address' => 'Botanická 68a, Brno – Královo Pole',
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

        if (str_contains($text, 'kamenice') || str_contains($text, 'kampus bohunice') || str_contains($text, 'bohunick')) {
            return [
                'lat' => 49.1772,
                'lng' => 16.5684,
                'title' => 'Univerzitní kampus Bohunice (MUNI)',
                'address' => 'Kamenice 5, Brno – Bohunice',
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
}
