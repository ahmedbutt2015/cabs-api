<?php

namespace App\Http\Controllers\Api;

use App\Helpers\LocationHelper;
use App\Helpers\LocationTrait;
use App\Helpers\NotificationTrait;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\LaterBooking;
use App\Models\Location;
use App\Models\PasswordReset;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    use LocationTrait, LocationHelper;

    function booking_history(Request $request)
    {

        $bookings = Booking::whereIn('status', ['completed', 'cancel'])->where('passengers_id', $request->uid)->get();
        foreach ($bookings as $booking) {
            unset($booking->driver_id);
            unset($booking->passengers_id);
            unset($booking->fare);
            unset($booking->status);
            unset($booking->remarks);
            unset($booking->payment_method);
            unset($booking->updated_at);
            $booking->booking_id = $booking->id;
        }
        return response()->json([
            'server_response' => $bookings
        ]);
    }

    function booking_history_schedule(Request $request)
    {

        $bookings = Booking::whereIn('status', ['completed', 'InCommunication'])->where('passengers_id', $request->uid)->get();
        $later_bookings = LaterBooking::whereIn('status', ['NotConfirmed', 'confirmed'])->where('passengers_id', $request->uid)->get();
        $a = $bookings->merge($later_bookings);
        foreach ($bookings as $booking) {
            unset($booking->driver_id);
            unset($booking->passengers_id);
            unset($booking->fare);
            unset($booking->status);
            unset($booking->remarks);
            unset($booking->payment_method);
            unset($booking->updated_at);
            $booking->booking_id = $booking->id;
        }
        return response()->json([
            'server_response' => $a
        ]);
    }

    function booking_history_detail(Request $request)
    {
        $booking = Booking::where('booking.id', $request->booking_id)
            ->join('drivers', 'booking.driver_id', '=', 'drivers.id')
            ->join('vehicle', 'drivers.vehicle_id', '=', 'vehicle.id')
            ->join('cartype', 'vehicle.cartype_id', '=', 'cartype.id')
            ->select('booking.*', 'drivers.name as driver_name', 'vehicle.name as car_name', 'vehicle.reg_no as car_reg', 'cartype.type as cartype')
            ->get();
        $booking->duration = 100;
        return response()->json([
            'server_response' => $booking
        ]);
    }

    function add_location(Request $request)
    {
        Location::create([
            'name' => $request->input('tagname'),
            'address' => $request->input('placeaddress'),
            'passengers_id' => $request->input('id'),
        ]);
        return response()->json(['server_response' => [[
            'error' => false,
            'status' => "Location Added"
        ]]]);
    }

    function getLatLong($address)
    {
        $address = $address;
        $prepAddr = str_replace(' ', '+', $address);
        $geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
        $output = json_decode($geocode);
        if (count($output->results)) {
            $t = [];
            $t['latitude'] = $output->results[0]->geometry->location->lat;
            $t['longitude'] = $output->results[0]->geometry->location->lng;
            return $t;
        } else {
            return false;
        }
    }

    function change_password(Request $request)
    {

        $data = $request->all();
        if ($driver = User::find($data['id'])) {
            if (password_verify($data['currentpassword'], $driver->password)) {

                $driver->password = password_hash($data['newpassword'], PASSWORD_BCRYPT);
                $driver->save();
                return response()->json([
                    'server_response' => [
                        [
                            'error' => false,
                            'status' => 'Password Chagned',
                        ]
                    ]
                ]);
            }
        }
        return response()->json(['server_response' => [['error' => true, 'status' => "Wrong Old Password"]]]);
    }


    function logout(Request $request)
    {

        $driver = User::find($request->id);
        $driver->device_id = null;
//        $driver->status = "offline";
        $driver->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Logout Successfully"]]]);
    }


    function locations(Request $request)
    {

        $driver = Location::where('passengers_id', $request->id)->get();
        return response()->json(['server_response' => $driver]);
    }


    function booking(Request $request)
    {
        $b = Booking::create([
            'passengers_id' => $request->id,
            'source' => $request->source,
            'destination' => $request->destination,
            'payment_method' => 'cash',
            'fare' => '',
            'status' => 'InCommunication'
        ]);
        if (!$this->request_driver_booking($b, $b->id, $request->cartype_id)) {
            $b->status = "DriverNotFound";
            $b->remarks = "Driver Not Found";
            $b->save();
            return response()->json(['server_response' => [['error' => true, 'status' => "Driver Not found"]]]);
        }
        return response()->json(['server_response' => "Booking Created !!","booking_id" => $b->id]);
    }

    function later_book(Request $request)
    {

        $timeSplit = explode("/", $request->date);
        $newTime = $timeSplit[2] . "-" . $timeSplit[1] . "-" . $timeSplit[0];
        $dtToronto = new Carbon($newTime . ' ' . $request->time);
        $diff = Carbon::now()->diffInHours($dtToronto);
//        dd($request->all());
        if ($diff > 1) {
            $tempBooking = LaterBooking::create([
                'passengers_id' => $request->id,
                'source' => $request->source,
                'destination' => $request->destination,
                'payment_method' => 'cash',
                'fare' => '',
                // cartype
                'status' => 'NotConfirmed',
                'start_ride_at' => $dtToronto
            ]);
            return response()->json(['server_response' => [['error' => false, 'booking_id' => $tempBooking->id]]]);
//            $this->notify_all();
        } else {
            return response()->json(['server_response' => [['error' => true, 'status' => "Too short time"]]]);
        }
    }

    function booking_cancel(Request $request)
    {
        $booking = LaterBooking::find($request->booking_id);
        $booking->status = "cancel";
        $booking->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Booking Cancel"]]]);
    }

    function booking_detail(Request $request)
    {
        $booking = LaterBooking::find($request->booking_id);
        return response()->json(['server_response' => [['error' => false, 'status' => "Booking Found", "booking" => $booking]]]);
    }

    function estimated_time(Request $request)
    {
        $driver = $this->retrieve_free_driver($request->lati, $request->longi, $request->cartype);
        if ($driver == null) {
            return response()->json(['server_response' => [['error' => true, 'status' => "No Driver Found"]]]);
        }
        $dist = $this->GetDrivingDistance($driver->latitude, $driver->longitude, $request->lati, $request->longi);
        return response()->json(['server_response' => [['error' => false, 'status' => "Driver Found", "time" => $dist["time"]]]]);
    }

    private function retrieve_free_driver($lat, $log, $id)
    {

        /*SELECT * FROM drivers INNER JOIN vehicle ON drivers.vehicle_id = vehicle.id INNER JOIN cartype ON vehicle.cartype_id = cartype.id WHERE drivers.status = "free" AND vehicle.cartype_id = 1*/
        $drivers = DB::table("drivers")
            ->join("vehicle", "drivers.vehicle_id", "=", "vehicle.id")
            ->join("cartype", "vehicle.cartype_id", "=", "cartype.id")
            ->where("drivers.status", "free")
            ->where("vehicle.cartype_id", $id)->get();

        foreach ($drivers as $driver) {
            if ($this->getDistanceBetweenPoints(
                    $driver->latitude, $driver->longitude, $lat, $log) <= 5
            ) {
                return $driver;
            }
        }
        return null;
    }

    function forget_password(Request $request)
    {

        $u = User::where("email", $request->email)->first();
        if ($u == null) {
            return response()->json(['server_response' => [['error' => true, 'status' => "Email not Found"]]]);
        }
        $token = md5($request->email . Carbon::now());
        $token = str_replace(array('/'), '', $token);
        PasswordReset::create([
            "email" => $request->email,
            "token" => $token,
        ]);
        $e = $request->email;
        Mail::send('emails.confirmation', ['token' => $token], function ($m) use ($e) {
            $m->from('no-reply@cabsway.com', 'Beehive Pharmacy');
            $m->to($e)->subject('Forgot Password ! ');
        });
        return response()->json(['server_response' => [['error' => false, 'status' => "Email has been sent"]]]);
    }

    function is_booking_assigned(Request $request)
    {
        $booking = Booking::find($request->booking_id);
        if($booking == null){
            return response()->json(['server_response' => [['error' => true, 'status' => "Booking not Found"]]]);
        }
        if ($booking->driver_id == null) {
/*            if ($booking->status === "DriverNotFound") {
                return json_encode(['found' => false, 'type' => true]);
            }*/
            return response()->json(['server_response' => [['error' => true, 'status' => "Driver Not Assigned"]]]);
        }
        $d = Driver::find($booking->driver_id);
        $v = Vehicle::find($d->vehicle_id);
        $d->vehicle_number = $v->reg_no;
        $d->estimated_time = $this->GetDrivingDistance2($booking->source, $d->latitude, $d->longitude);
        return response()->json(['server_response' => [['error' => false, 'driver' => $d]]]);

    }

    function cancel(Request $request)
    {
        $booking = Booking::find($request->booking_id);
        $booking->status = "cancel";
        $booking->save();
        if($booking->status == "completed"){
            return response()->json(['server_response' => [['error' => true, 'status' => "Booking is completed"]]]);
        }
        $d = Driver::find($request->driver_id);
        $message = array("PushType" => "cancel");
        $tokens = array();
        array_push($tokens, $d->device_id);
        $this->send_notification($tokens, $message);
        return response()->json(['server_response' => [['error' => false, 'status' => "Booking Cancel"]]]);
    }
}
