<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'violation_id',
        'file_path',
        'file_type',
        'uploaded_by',
        'recorded_at',
    ];

    public function violation(): BelongsTo
    {
        return $this->belongsTo(Violation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
