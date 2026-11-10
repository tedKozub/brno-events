<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'latitude',
        'longitude',
        'website_url',
        'description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected static function booted()
    {
        static::creating(function ($venue) {
            if (empty($venue->slug)) {
                $venue->slug = Str::slug($venue->name);
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
