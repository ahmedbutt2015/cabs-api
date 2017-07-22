<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\Http\Controllers\Controller;
use App\Helpers\AuthTrait;

class AothController extends Controller
{
    use AuthTrait;
    public function register(Request $request){

        $data = $request->all();
        $token = app('hash')->make($data['email'] . Carbon::now());
        $token = str_replace(array('/'), '',$token);
        $temp = User::where('email',$data['email'])->get();

        if(count($temp)) {
            return response()->json(['server_response' => [['error' => true,'status' => 'Email belong to anyone else']]]);
        }

        if($this->create($data,$token)){
            $this->sendMail($data,$token);
            return response()->json(['server_response' => [['error' => false,'status' => 'User Created']]]);
        }else{
            return response()->json(['server_response' => [['error' => true,'status' => 'Something went wrong']]]);
        }
    }

    public function login(Request $request){

        $name = $request->input('email');
        $password = $request->input('password');
        $a = User::where('email', '=', $name)->get();
        if(count($a) ){
            if(password_verify($password,$a[0]->password)){
                return response()->json([
                    'server_response' => [
                        [
                            'error' => false,
                            'status' => 'Login Successful',
                            'id' => $a[0]->id."",
                            'name' => $a[0]->name,
                            'email' => $a[0]->email,
                            'phone' => $a[0]->phone
                        ]
                    ]]);

            }
        }
        return response()->json(['server_response' => [['status' => "Invalid Credential",'error'=>true]]]);
    }
}
