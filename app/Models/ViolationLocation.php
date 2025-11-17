<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class ViolationLocation extends Model
{
      use  HasFactory, Notifiable , HasApiTokens  ;

            protected $fillable = [ 'address' , 'street_name' , 'land_mark' ];


       public function violations(){
            return $this->hasMany(Violation::class , 'Violation_id');
       }

}
