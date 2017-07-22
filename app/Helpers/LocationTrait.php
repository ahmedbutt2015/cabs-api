<?php
/**
 * Created by PhpStorm.
 * User: dev
 * Date: 11/8/16
 * Time: 9:51 PM
 */
namespace App\Helpers;

use Illuminate\Support\Facades\DB;

trait LocationTrait{

    use NotificationTrait;
    private function retrieve_free_drivers($latLog,$type)
    {
        /*SELECT * FROM drivers INNER JOIN vehicle ON drivers.vehicle_id = vehicle.id INNER JOIN cartype ON vehicle.cartype_id = cartype.id WHERE drivers.status = "free" AND vehicle.cartype_id = 1*/
        $drivers = DB::table("drivers")
            ->join("vehicle", "drivers.vehicle_id", "=", "vehicle.id")
            ->join("cartype", "vehicle.cartype_id", "=", "cartype.id")
            ->where("drivers.status", "free")
            ->where("vehicle.cartype_id", $type)->get();

        foreach ($drivers as $driver) {
            if ($this->getDistanceBetweenPoints(
                $driver->latitude, $driver->longitude, $latLog['latitude'], $latLog['longitude']) <= 5
            ) {
                return $driver;
            }
        }
    }

    public function getLatLong($address){
        $address = $address;
        $prepAddr = str_replace(' ','+',$address);
        $geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
        $output= json_decode($geocode);
        if(count($output->results)){
            $t = [];
            $t['latitude'] = $output->results[0]->geometry->location->lat;
            $t['longitude'] = $output->results[0]->geometry->location->lng;
            return $t;
        }else{
            return false;
        }

    }

    /*function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$lat1.",".$lon1."&destinations=".$lat2.",".$lon2."&mode=driving&language=pl-PL";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response, true);
        if(!isset($response_a['rows'][0]['elements'][0]['distance']) ){
            return array('status'=>false);
        }
        $dist = $response_a['rows'][0]['elements'][0]['distance']['text'] * 0.621371;
        $time = $response_a['rows'][0]['elements'][0]['duration']['text'];
        return $dist;
    }*/

    function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        return $miles;
    }

    private function request_driver_booking($request,$id,$type)
    {
        $latLog = $this->getLatLong($request->source);
        $driver = $this->retrieve_free_drivers($latLog,$type);
        if ($driver == null) {
            return false;
        }
        // Send this driver notification using firebase .
        $message = array("PushType" => "booking","booking_id" => $id);
        $tokens = array();
        array_push($tokens, $driver->device_id);
        $this->send_notification($tokens, $message);
        return true;
    }
}