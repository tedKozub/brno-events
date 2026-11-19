<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'address',
        'city',
        'latitude',
        'longitude',
        'osm_place_id',
        'place_type',
        'is_verified',
        'website_url',
        'description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'osm_place_id' => 'integer',
        'is_verified' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($venue) {
            if (empty($venue->slug)) {
                $baseSlug = Str::slug($venue->name);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . (++$counter);
                }
                $venue->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Venue::class, 'parent_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(VenueAlias::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function upcomingEvents(): HasMany
    {
        return $this->hasMany(Event::class)
            ->where('starts_at', '>=', now()->startOfDay())
            ->published();
    }
}
