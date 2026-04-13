<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerLiveLocation extends Model
{
    public $timestamps = false;

    protected $table = 'officers_live_locations';

    protected $fillable = [
        'officer_id',
        'latitude',
        'longitude',
        'availability_status',
        'last_update_time',
        'device_id',
        'battery_level',
        'created_at',
        'updated_at',
    ];

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
