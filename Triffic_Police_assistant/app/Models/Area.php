<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'city',
        'center_lat',
        'center_lng',
        'created_at',
    ];

    public function reportLocations(): HasMany
    {
        return $this->hasMany(ReportLocation::class);
    }

    public function violationLocations(): HasMany
    {
        return $this->hasMany(ViolationLocation::class);
    }
}
