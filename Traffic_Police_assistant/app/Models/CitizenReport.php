<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CitizenReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reporter_name',
        'reporter_phone',
        'report_location_id',
        'title',
        'description',
        'image_path',
        'status',
        'priority',
        'submitted_at',
        'created_at',
        'assigned_officer_id',
        'accepted_at',
        'closed_at',
        'dispatch_attempts_count',
        'last_dispatch_at',
    ];

    public function reportLocation(): BelongsTo
    {
        return $this->belongsTo(ReportLocation::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReportAssignment::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class, 'source_report_id');
    }
}
