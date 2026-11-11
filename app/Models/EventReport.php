<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'reason',
        'details',
        'contact_email',
        'status',
        'admin_notes',
    ];

    public static array $reasons = [
        'cancelled' => 'Akce byla zrušena / nekoná se',
        'wrong_time' => 'Nesprávné datum nebo čas konání',
        'wrong_location' => 'Chybné místo konání či adresa',
        'wrong_organizer' => 'Špatný pořadatel / spolek',
        'missing_info' => 'Chybí důležité informace',
        'spam' => 'Spam, nevhodný obsah nebo duplicita',
        'other' => 'Jiný problém',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getReasonLabelAttribute(): string
    {
        return self::$reasons[$this->reason] ?? $this->reason;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
