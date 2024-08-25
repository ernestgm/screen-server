<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marquee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'business_id',
        'bg_color',
        'text_color',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function ads(): HasMany {
        return $this->hasMany(Ad::class, 'marquee_id', 'id');
    }

    public function devices(): HasMany {
        return $this->hasMany(Device::class, 'marquee_id', 'id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
