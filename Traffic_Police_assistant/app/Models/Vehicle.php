<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = ['plate_number', 'owner_name', 'model', 'color'];

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
