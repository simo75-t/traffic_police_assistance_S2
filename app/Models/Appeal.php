<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    protected $fillable = ['status' , 'reason' , 'decision_note'];


    public function violations(){
        return $this->hasMany(Violation::class);
    }
}
