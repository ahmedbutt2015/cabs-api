<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'vehicle';

    public function cartype(){

        return $this->belongsTo('App\Models\CarType','cartype_id');
    }
    

}
