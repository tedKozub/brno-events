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

        static::saving(function ($org) {
            foreach (['website_url', 'facebook_url', 'logo_url'] as $urlField) {
                if (!empty($org->{$urlField})) {
                    $scheme = strtolower(parse_url($org->{$urlField}, PHP_URL_SCHEME) ?? '');
                    if (!in_array($scheme, ['http', 'https'], true)) {
                        $org->{$urlField} = null;
                    }
                }
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

    public function getTypeAttribute(): string
    {
        if (in_array($this->slug, ['muni', 'vut', 'mendelu', 'unob', 'vetuni'])) {
            return 'university';
        }
        if (str_starts_with($this->slug, 'muni-') || str_starts_with($this->slug, 'vut-') || str_starts_with($this->slug, 'mendelu-')) {
            return 'faculty';
        }
        return 'spolek';
    }

    public function getIsSpolekAttribute(): bool
    {
        return $this->type === 'spolek';
    }
}
