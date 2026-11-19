<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VenueAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'alias',
        'alias_normalized',
    ];

    protected static function booted()
    {
        static::saving(function ($alias) {
            if (empty($alias->alias_normalized) && !empty($alias->alias)) {
                $alias->alias_normalized = self::normalize($alias->alias);
            }
        });
    }

    public static function normalize(string $value): string
    {
        $ascii = Str::ascii($value);
        $lower = mb_strtolower($ascii);
        return trim(preg_replace('/[^\w\s]/u', ' ', $lower));
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
