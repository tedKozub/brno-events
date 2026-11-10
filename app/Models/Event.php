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
}
