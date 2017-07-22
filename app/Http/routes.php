<?php

use Illuminate\Support\Facades\Mail;

$app->group(['prefix' => 'api', 'namespace' => 'App\Http\Controllers\Api'], function ($app) {

    $app->post('/driver/login', 'DriverController@login');
    $app->post('/driver/logout', 'DriverController@logout');
    $app->post('/driver/{id}', 'DriverController@get_driver');
    $app->post('/driver/location/{id}', 'DriverController@get_addr');
    $app->post('/driver/password/change', 'DriverController@change_password');
    $app->post('/driver/location/update', 'DriverController@update_location');
    $app->post('/driver/status/update', 'DriverController@status_update');
    $app->post('/driver/accept/booking', 'DriverController@accept_booking');
    $app->post('/driver/start/ride', 'DriverController@start_ride');
    $app->post('/driver/stop/ride', 'DriverController@stop_ride');
    $app->post('/driver/booking/history', 'DriverController@booking_history_schedule');
    $app->post('/driver/booking/detail', 'DriverController@booking_detail');

    $app->get('/notify', 'DriverController@notify');

    $app->post('/login', 'AothController@login');
    $app->post('/register', 'AothController@register');
    $app->post('/change/password', 'UserController@change_password');
    $app->post('/logout', 'UserController@logout');
    $app->post('/add/location', 'UserController@add_location');
    $app->post('/user/locations', 'UserController@locations');
    $app->post('/booking/history', 'UserController@booking_history');
//    $app->post('/booking/schedule', 'UserController@booking');
    $app->post('/booking/history/detail', 'UserController@booking_history_detail');
    $app->post('/user/booking/later', 'UserController@later_book');
    $app->post('/user/booking/ride', 'UserController@booking');
    $app->post('/is/booking/assigned', 'UserController@is_booking_assigned');
    $app->post('/booking/schedule', 'UserController@booking_history_schedule');
    $app->post('/booking/schedule/cancel', 'UserController@booking_cancel');
    $app->post('/booking/schedule/detail', 'UserController@booking_detail');
    $app->post('/user/estimated/time', 'UserController@estimated_time');
    $app->post('/booking/cancel', 'UserController@cancel');
    $app->register(Illuminate\Mail\MailServiceProvider::class);
    $app->configure('mail');
    $app->post('/forget/password', 'UserController@forget_password');

    $app->get("/ahmed", function () {
        if (Mail::send('reminder', [], function ($m) {
            $m->from('bahtasham@gmail.com', 'Ahmed Butt');
            $m->to('ahmedbuttdev@gmail.com', 'Waleed Ahmad')->subject('This is a reminder');
        })
        ) {
            return "Email Sent";
        }
    });
//    $app->get('/user','DriverController@start_later_bookings');
});