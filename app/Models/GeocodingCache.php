<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeocodingCache extends Model
{
    use HasFactory;

    protected $table = 'geocoding_cache';
    protected $primaryKey = 'query_hash';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'query_hash',
        'raw_query',
        'latitude',
        'longitude',
        'osm_place_id',
        'osm_type',
        'formatted_address',
        'raw_response',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'osm_place_id' => 'integer',
        'raw_response' => 'array',
    ];

    public static function hashQuery(string $query): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $query)));
        return hash('sha256', $normalized);
    }
}
