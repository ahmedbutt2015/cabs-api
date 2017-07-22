<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Mail\Mailer;

trait AuthTrait{

    protected function sendMail($data,$token){

//        Mail::send('emails.confirmation', ['user' => $data,'token'=>$token], function ($m) use ($data){
//            $m->from('bahtasham@gmail.com', 'Acme');
//            $m->to($data['email'], $data['name'])->subject('Confirmation Email!');
//        });
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data,$token)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'verified' => 0,
            'verify_token' => $token,
            'password' => app('hash')->make($data['password']),
        ]);
    }
}