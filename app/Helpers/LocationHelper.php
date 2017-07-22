<?php
namespace App\Helpers;
trait LocationHelper {
   
    function GetDrivingDistance($lat1,$lon1,$lat2,$lon2)
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
        $dist = $response_a['rows'][0]['elements'][0]['distance']['text'];
        $time = $response_a['rows'][0]['elements'][0]['duration']['text'];
//        dd(array('distance' => $dist, 'time' => $time));
        return array('distance' => $dist, 'time' => $time);
    }

    function GetDrivingDistance2($src, $lat, $lon)
    {
        $l1 = $this->getLatLong($src);
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$l1['latitude'].",".$l1['longitude']."&destinations=".$lat.",".$lon."&mode=driving&language=pl-PL";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response, true);
        if(!isset($response_a['rows'][0]['elements'][0]['duration'])){
            return 0;
        }
        $time = $response_a['rows'][0]['elements'][0]['duration']['text'];
//        dd(array('distance' => $dist, 'time' => $time));
        return  $time;
    }
}