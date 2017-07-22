<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admins';

    protected $fillable = [
        'name','phone', 'email', 'password','driving_license','status','ssn','vehicle_id',
    ];

}
