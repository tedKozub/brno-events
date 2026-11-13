<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'event_id',
        'path',
        'category_slug',
        'university',
        'search_query',
        'device_type',
        'visitor_hash',
        'referrer_host',
        'country_code',
        'traffic_type',
        'is_internal',
        'created_at',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function scopeReal($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
