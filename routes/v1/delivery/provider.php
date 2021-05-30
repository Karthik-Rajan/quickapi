<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->group(['middleware' => 'auth:provider'], function($app) {
	
	$app->get('/check/delivery/request', 'V1\Delivery\Provider\TripController@index');

	$app->patch('/update/delivery/request', 'V1\Delivery\Provider\TripController@update_ride');

	$app->post('/cancel/delivery/request', 'V1\Delivery\Provider\TripController@cancel_ride');

	$app->post('/rate/delivery', 'V1\Delivery\Provider\TripController@rate');

    $app->post('/delivery/payment', 'V1\Delivery\User\DeliveryController@payment');

	$app->get('/history/delivery', 'V1\Delivery\Provider\TripController@trips');
	$app->get('/history/delivery/{id}', 'V1\Delivery\Provider\TripController@gettripdetails');
	$app->get('/history-dispute/delivery/{id}', 'V1\Delivery\Provider\TripController@get_ride_request_dispute');
	$app->post('/history-dispute/delivery', 'V1\Delivery\Provider\TripController@ride_request_dispute');
	 
	// $app->post('/ride_request_dispute/{id}', 'V1\Transport\Provider\TripController@ride_request_dispute');
	// $app->get('/get_ride_request_dispute/{id}', 'V1\Transport\Provider\TripController@get_ride_request_dispute');
	$app->get('/delivery/dispute', 'V1\Delivery\Provider\TripController@getdisputedetails');
	$app->get('/deliverytype', 'V1\Delivery\Provider\HomeController@deliverytype');


	$app->post('delivery/waiting', 'V1\Delivery\Provider\TripController@waiting');

	$app->get('/delivery/disputestatus/{id}', 'V1\Delivery\Provider\TripController@get_ride_request_dispute');

	
});