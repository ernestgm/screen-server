<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'device_id',
        'user_id',
        'screen_id',
        'marquee_id',
        'default_screen_id',
        'default_marquee_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    public function defaultScreen(): BelongsTo
    {
        return $this->belongsTo(Screen::class, 'default_screen_id');
    }

    public function defaultMarquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class, 'default_marquee_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DeviceSchedule::class, 'device_id', 'id');
    }
}
