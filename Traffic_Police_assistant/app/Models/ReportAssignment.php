<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'citizen_report_id',
        'officer_id',
        'assignment_order',
        'distance_km',
        'assignment_status',
        'assigned_at',
        'responded_at',
        'response_deadline',
        'notes',
    ];

    public function citizenReport(): BelongsTo
    {
        return $this->belongsTo(CitizenReport::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
