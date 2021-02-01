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
$router->group(['middleware' => 'authless:user'], function($app) {

    $app->get('/delivery/services', 'V1\Delivery\User\DeliveryController@services');

	$app->post('/delivery/estimate', 'V1\Delivery\User\DeliveryController@estimate');

	$app->get('/delivery/package/types', 'V1\Delivery\User\DeliveryController@package_types');

	$app->get('/delivery/types/{category_id}', 'V1\Delivery\User\DeliveryController@delivery_types');

});

$router->group(['middleware' => 'auth:user'], function($app) {

	$app->get('/delivery/packages/{category_id}', 'V1\Delivery\User\DeliveryController@package_types');

	$app->post('/delivery/send/request', 'V1\Delivery\User\DeliveryController@create_request');

	$app->get('/delivery/check/request', 'V1\Delivery\User\DeliveryController@status');

	$app->get('/delivery/request/{id}', 'V1\Delivery\User\DeliveryController@checkDelivery');

	$app->post('/delivery/payment', 'V1\Delivery\User\DeliveryController@payment');

	$app->post('/delivery/rate', 'V1\Delivery\User\DeliveryController@rate'); 

	$app->post('/delivery/cancel/request', 'V1\Delivery\User\DeliveryController@cancel_ride');

	$app->get('/trips-history/delivery', 'V1\Delivery\User\HomeController@trips');

	$app->get('/trips-history/delivery/{id}', 'V1\Delivery\User\HomeController@gettripdetails');

	$app->get('/delivery/dispute', 'V1\Delivery\User\HomeController@getdispute');

	$app->get('/delivery/dispute/{id}', 'V1\Delivery\User\HomeController@getUserdisputedetails');

	$app->post('/delivery/dispute', 'V1\Delivery\User\HomeController@delivery_request_dispute');
	
	$app->get('/delivery/disputestatus/{id}', 'V1\Delivery\User\HomeController@get_ride_request_dispute');
	
});