<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Violation extends Model
{
            use  HasFactory, Notifiable , HasApiTokens  ;

    protected $fillable = [
    'vehicle_id',
    'violation_type_id',
    'violation_location_id',
    'reported_by',
    'source_report_id',
    'description',
    'fine_amount',
    'vehicle_snapshot',
    'plate_snapshot',
    'owner_snapshot',
    'occurred_at',
    'data_source',
    'is_synthetic',
    'severity_level',
    'status',
];


public function vehicle(): BelongsTo
{
    return $this->belongsTo(Vehicle::class);
}

public function violationLocation(): BelongsTo
{
    return $this->belongsTo(ViolationLocation::class);
}

public function violationType(): BelongsTo
{
    return $this->belongsTo(ViolationType::class);
}


public function reporter(): BelongsTo
{
    return $this->belongsTo(User::class, 'reported_by');
}

public function appeal(): HasOne
{
    return $this->hasOne(Appeal::class);
}

public function sourceReport(): BelongsTo
{
    return $this->belongsTo(CitizenReport::class, 'source_report_id');
}

public function attachments(): HasMany
{
    return $this->hasMany(Attachment::class);
}
}
