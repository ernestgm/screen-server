<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;


class Business extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'logo',
        'user_id'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function getLocation(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function geolocation(): HasOne
    {
        return $this->hasOne(GeoLocation::class, 'business_id', 'id');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'business_id', 'id');
    }

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class, 'business_id', 'id');
    }

    public function marquees(): HasMany
    {
        return $this->hasMany(Marquee::class, 'business_id', 'id');
    }
}
