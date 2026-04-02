<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportLocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'area_id',
        'address',
        'street_name',
        'landmark',
        'latitude',
        'longitude',
        'city',
        'created_at',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function citizenReports(): HasMany
    {
        return $this->hasMany(CitizenReport::class);
    }
}
