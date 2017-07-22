<?php

namespace App\Http\Controllers\Api;

use App\Helpers\LocationHelper;
use App\Helpers\LocationTrait;
use App\Helpers\NotificationTrait;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\LaterBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;

use App\Http\Controllers\Controller;

class DriverController extends Controller
{
    use LocationTrait;

    public function login(Request $request)
    {

//        Driver::find
        $name = $request->input('email');
        $password = $request->input('password');
        $a = Driver::where('email', '=', $name)->get();
        if (count($a)) {
            $v = $a[0]->vehicles;
            if (password_verify($password, $a[0]->password)) {
                $a[0]->device_id = $request->device_id;
                $a[0]->status = 'free';
                $a[0]->save();
                return response()->json([
                    'server_response' => [
                        [
                            'error' => false,
                            'status' => 'Login Successful',
                            'id' => $a[0]->id . "",
                            'name' => $a[0]->name,
                            'email' => $a[0]->email,
                            'phone' => $a[0]->phone,
                            'driving_license' => $a[0]->driving_license,
                            'driver_status' => $a[0]->status,
                            'ssn' => $a[0]->ssn . "",
                            'vehicle_name' => $v->name,
                            'vehicle_model' => $v->model,
                            'vehicle_type' => $v->cartype->type
                        ]
                    ]
                ]);
            }
        }
        return response()->json(['server_response' => [['error' => true, 'status' => "Invalid Email or Password"]]]);
    }

    public function get_driver($id)
    {

        if ($driver = Driver::find($id)) {

            $v = $driver->vehicles;
            return response()->json([
                'server_response' => [
                    [
                        'error' => false,
                        'status' => 'Admin Found',
                        'id' => $driver->id . "",
                        'name' => $driver->name,
                        'email' => $driver->email,
                        'phone' => $driver->phone,
                        'driving_license' => $driver->driving_license,
                        'driver_status' => $driver->status,
                        'ssn' => $driver->ssn . "",
                        'vehicle_name' => $v->name,
                        'vehicle_model' => $v->model,
                        'vehicle_type' => $v->cartype->type,
                    ]
                ]
            ]);
        }
        return response()->json(['server_response' => [['error' => true, 'status' => "Driver not found"]]]);
    }

    public function change_password(Request $request)
    {

        $data = $request->all();
        if ($driver = Driver::find($data['id'])) {

            if (password_verify($data['old'], $driver->password)) {

                $driver->password = password_hash($data['new'], PASSWORD_BCRYPT);
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

    public function get_addr($id)
    {

        if ($a = Driver::where('id', $id)->first()) {
            return response()->json([
                'server_response' => [
                    [
                        'error' => false,
                        'status' => 'Admin Found',
                        'address' => $a->address,
                    ]
                ]
            ]);
        }
        return response()->json(['server_response' => [['error' => true, 'status' => "Driver not found"]]]);
    }

    function update_location(Request $request)
    {

        $d = Driver::find($request->id);
        $d->longitude = doubleval($request->longitude);
        $d->latitude = doubleval($request->latitude);
        $d->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Location Updated"]]]);
    }

    function logout(Request $request)
    {

        $driver = Driver::find($request->id);
        $driver->device_id = null;
        $driver->status = "offline";
        $driver->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Logout Successfully"]]]);
    }

    function accept_booking(Request $request)
    {

        $booking = Booking::find($request->booking_id);
        $d = Driver::find($request->id);
        if ($booking->status === "cancel") {
            $d->status = "free";
            $d->save();
            return response()->json(['server_response' => [['error' => true, 'status' => "canceled"]]]);
        } else {
            $booking->driver_id = $request->id;
            $booking->save();
            $d->status = $request->status;
            $d->save();
            $uy = User::find($booking->passengers_id);
            return response()->json([
                'server_response' => [[
                    'error' => false,
                    'id' => $booking->id,
                    'source' => $booking->source,
                    'destination' => $booking->destination,
                    'phone' => $uy->phone,
                ]]
            ]);
        }


    }

    function status_update(Request $request)
    {

        $d = Driver::find($request->id);
        $d->status = $request->status;
        $d->save();
        if ($request->status === "notResponding") {
            $b = Booking::find($request->booking_id);
            if (!$this->request_driver_booking($b, $b->id)) {
                $b->status = "DriverNotFound";
                $b->remarks = "Driver Not Found";
                $b->save();
            }
        }
        return response()->json(['server_response' => [['error' => false, 'status' => "Status Updated"]]]);
    }

    function notify()
    {
        $d = Driver::where("device_id", "<>", "")->get();
        $message = array("PushType" => "updateLocation");
        $tokens = array();
        foreach ($d as $dr) {
            array_push($tokens, $dr->device_id);
        }
        $this->send_notification($tokens, $message);
    }

    function start_ride(Request $request)
    {

        $booking = Booking::find($request->booking_id);
        $booking->pickup = $request->pickup;
        $booking->started_at = Carbon::now();
        $booking->status = "onRide";
        $booking->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Pickup Updated"]]]);
    }

    function stop_ride(Request $request)
    {

        $booking = Booking::find($request->booking_id);
        $booking->dropoff = $request->drop;
        $booking->finished_at = Carbon::now();
        $booking->status = "completed";
/*        $p1 = explode(",", $booking->pickup);
        $p2 = explode(",", $bfqafaooking->dropoff);
        $d = $this->getDistanceBetweenPoints($p1[0],$p1[1],$p2[0],$p2[1]);
        if ($d > 1 && $d <= 5) {
            $d =  $d * 3;
        } elseif ($d > 5 && $d <= 10) {
            $d =  $d * 2.5;
        } else {
            $d =  $d * 2;
        }
        $booking->fare = $d;
        $booking->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Dropoff Updated"]]]);*/
        $t1 = $this->getLatLong($booking->pickup);
        $t2 = $this->getLatLong($booking->dropoff);
        $d = $this->getDistanceBetweenPoints($t1["latitude"],$t1["longitude"],$t2["latitude"],$t2["longitude"]);
        if ($d > 1 && $d <= 5) {
            $d =  $d * 3;
        } elseif ($d > 5 && $d <= 10) {
            $d =  $d * 2.5;
        } else {
            $d =  $d * 2;
        }
        $booking->fare = $d;
        $booking->save();
        return response()->json(['server_response' => [['error' => false, 'status' => "Dropoff Updated"]]]);

    }

    function booking_history_schedule(Request $request)
    {
        $bookings = Booking::whereIn('status',['completed','cancel'])->where('driver_id', $request->id)->get();
        $later_bookings = LaterBooking::whereIn('status',['completed', 'cancel'])->where('driver_id', $request->id)->get();
        $sce = LaterBooking::whereIn('status',['confirmed'])->where('driver_id', $request->id)->get();
        $a = $bookings->merge($later_bookings);
        foreach ($bookings as $booking) {
/*            unset($booking->driver_id);
            unset($booking->passengers_id);
            unset($booking->fare);
            unset($booking->status);
            unset($booking->remarks);
            unset($booking->payment_method);
            unset($booking->updated_at);*/
            $booking->booking_id = $booking->id;
        }
        return response()->json([
            'error' => false,
            'status' => "Record Found",
            'complete_ride' => $a,
            'schedule_ride' => $sce,
        ]);
    }

    function booking_detail(Request $request){
        $booking = Booking::find($request->booking_id);
        /*$booking = Booking::where('booking.id', $request->booking_id)
            ->join('drivers', 'booking.driver_id', '=', 'drivers.id')
            ->join('vehicle', 'drivers.vehicle_id', '=', 'vehicle.id')
            ->join('cartype', 'vehicle.cartype_id', '=', 'cartype.id')
            ->select('booking.*', 'drivers.name as driver_name', 'vehicle.name as car_name', 'vehicle.reg_no as car_reg', 'cartype.type as cartype')
            ->get();*/
        return response()->json([
            'server_response' => [[
                'error' => false,
                'status' => "Record Found",
                'booking_detail' => $booking
            ]]
        ]);
    }

}
