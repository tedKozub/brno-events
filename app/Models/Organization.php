<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'university',
        'faculty',
        'description',
        'logo_url',
        'website_url',
        'instagram_handle',
        'facebook_url',
        'contact_email',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($org) {
            if (empty($org->slug)) {
                $org->slug = Str::slug($org->name);
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scrapeSources(): HasMany
    {
        return $this->hasMany(ScrapeSource::class);
    }
}
