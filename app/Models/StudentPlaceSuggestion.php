<?php

namespace App\Models;

use App\Services\GeocodingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentPlaceSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_place_category_id',
        'name',
        'address',
        'highlight',
        'description',
        'tags',
        'website_url',
        'instagram_handle',
        'submitter_name',
        'submitter_email',
        'status',
        'admin_notes',
        'approved_student_place_id',
        'ip_hash',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StudentPlaceCategory::class, 'student_place_category_id');
    }

    public function approvedPlace(): BelongsTo
    {
        return $this->belongsTo(StudentPlace::class, 'approved_student_place_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Approves this student suggestion and creates an active StudentPlace on the map.
     */
    public function approveAndCreatePlace(array $attributes = []): StudentPlace
    {
        $lat = $attributes['lat'] ?? null;
        $lng = $attributes['lng'] ?? null;

        if ($lat === null || $lng === null) {
            $coords = GeocodingService::geocodeAddress($this->address);
            $lat = $coords['lat'];
            $lng = $coords['lng'];
        }

        $baseSlug = Str::slug($this->name);
        $slug = $baseSlug;
        $counter = 1;
        while (StudentPlace::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$counter);
        }

        $place = StudentPlace::create([
            'student_place_category_id' => $attributes['student_place_category_id'] ?? $this->student_place_category_id,
            'name' => $attributes['name'] ?? $this->name,
            'slug' => $slug,
            'address' => $attributes['address'] ?? $this->address,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'description' => $attributes['description'] ?? $this->description,
            'highlight' => $attributes['highlight'] ?? $this->highlight,
            'tags' => $attributes['tags'] ?? ($this->tags ?: ['Studentský tip']),
            'website_url' => $attributes['website_url'] ?? $this->website_url,
            'instagram_handle' => $attributes['instagram_handle'] ?? $this->instagram_handle,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->update([
            'status' => 'approved',
            'approved_student_place_id' => $place->id,
            'admin_notes' => $attributes['admin_notes'] ?? $this->admin_notes,
        ]);

        return $place;
    }
}
