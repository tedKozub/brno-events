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
        'room_detail',
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

        static::saving(function ($event) {
            if (!empty($event->location_name)) {
                try {
                    $resolver = app(\App\Services\VenueResolverService::class);
                    if (empty($event->room_detail)) {
                        $extracted = $resolver->extractRoomAndLocation($event->location_name);
                        if (!empty($extracted['room'])) {
                            $event->room_detail = $extracted['room'];
                        }
                    }
                } catch (\Throwable $e) {}
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
        $room = trim($this->room_detail ?? '');
        $venueName = $this->venue?->name;

        if ($room !== '' && $venueName) {
            return "{$room} ({$venueName})";
        }

        if ($this->venue) {
            // Pokud location_name obsahuje specifickou učebnu/místnost, která nebyla oddělena
            if ($loc !== '' && !str_starts_with(mb_strtolower($loc), mb_strtolower($this->venue->name))) {
                if (preg_match('/(učebn|ucebn|místnost|mistnost|budov|sál|sal|aula|posluchárn|poslucharn|q\d+|lab)/ui', $loc)) {
                    $cleanLoc = preg_replace('/,?\s*brno\s*$/ui', '', $loc);
                    if (str_contains(mb_strtolower($cleanLoc), mb_strtolower($this->venue->name))) {
                        return $cleanLoc;
                    }
                    return "{$cleanLoc} ({$this->venue->name})";
                }
            }

            return $this->venue->address ? "{$this->venue->name}, {$this->venue->address}" : $this->venue->name;
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

        // If a lightweight .webp version exists, prefer it for faster LCP and lower data transfer
        $webpPath = preg_replace('/\.(png|jpe?g)$/i', '.webp', $cleanPath);
        if ($webpPath !== $cleanPath && Storage::disk('public')->exists($webpPath)) {
            return '/storage/' . $webpPath;
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

        $loc = trim($this->location_name ?? '');
        $hasCustomLoc = !empty($loc) && !in_array(mb_strtolower($loc), ['brno', 'brno centrum', 'brno - střed']);

        return [
            'lat' => 49.1951,
            'lng' => 16.6068,
            'title' => $hasCustomLoc ? $loc : 'Brno centrum',
            'address' => $hasCustomLoc ? (str_contains(mb_strtolower($loc), 'brno') ? $loc : ($loc . ', Brno')) : 'Brno centrum',
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
