<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    public function lon(){
        return $this->hasOne('App\Models\Location');
    }
    public function vehicles(){
        return $this->belongsTo('App\Models\Vehicle','vehicle_id');
    }
}
