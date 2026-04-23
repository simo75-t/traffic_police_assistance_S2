<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    protected $fillable = ['violation_id', 'status', 'reason', 'decision_note', 'submitted_at', 'decided_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function violation()
    {
        return $this->belongsTo(Violation::class);
    }
}
