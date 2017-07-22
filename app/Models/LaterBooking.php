<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaterBooking extends Model
{
    protected $table = 'later_booking';

    protected $fillable = ['passengers_id','status','source','destination','payment_method','start_ride_at'];

}
