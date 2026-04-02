<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationType extends Model
{
    protected $fillable = ['name' , 'description' , 'fine_amount', 'severity_weight', 'is_active'];

    protected $casts = [
        'fine_amount' => 'decimal:2',
        'severity_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
