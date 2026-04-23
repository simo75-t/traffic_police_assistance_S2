<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationLocation extends Model
{
     use HasFactory;

     public $timestamps = true;

     protected $fillable = [
         'area_id',
         'address',
         'street_name',
         'landmark',
         'latitude',
         'longitude',
         'city',
         'city_id',
     ];


     public function violations(): HasMany
     {
          return $this->hasMany(Violation::class);
     }

     public function cityRecord(): BelongsTo
     {
          return $this->belongsTo(City::class, 'city_id');
     }

     /**
      * Backward-compatible alias used by older resources/services.
      */
     public function city(): BelongsTo
     {
          return $this->belongsTo(City::class, 'city_id');
     }

     public function area(): BelongsTo
     {
          return $this->belongsTo(Area::class);
     }
}
