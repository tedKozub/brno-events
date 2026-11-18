<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StudentPlace extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_place_category_id',
        'name',
        'slug',
        'address',
        'lat',
        'lng',
        'description',
        'highlight',
        'tags',
        'website_url',
        'instagram_handle',
        'cover_image',
        'sort_order',
        'is_active',
        'embedding',
    ];

    protected $casts = [
        'lat'        => 'float',
        'lng'        => 'float',
        'tags'       => 'array',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($place) {
            if (empty($place->slug)) {
                $place->slug = Str::slug($place->name);
            }
        });

        // Invalidate allPlaces cache on any save/delete
        static::saved(function () {
            Cache::forget('student_places_all');
        });
        static::deleted(function () {
            Cache::forget('student_places_all');
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StudentPlaceCategory::class, 'student_place_category_id');
    }

    /**
     * Converts to array structure expected by Livewire frontend component and map JS.
     */
    public function toFrontendArray(): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'category'         => $this->category?->slug ?? 'study',
            'category_name'    => $this->category?->name ?? 'Místo',
            'category_color'   => $this->category?->color ?? '#6366f1',
            'address'          => $this->address,
            'lat'              => (float) $this->lat,
            'lng'              => (float) $this->lng,
            'description'      => $this->description,
            'tags'             => is_array($this->tags) ? $this->tags : [],
            'highlight'        => $this->highlight,
            'website_url'      => $this->website_url,
            'instagram_handle' => $this->instagram_handle,
            'embedding'        => $this->embedding ? json_decode($this->embedding, true) : null,
        ];
    }
}
