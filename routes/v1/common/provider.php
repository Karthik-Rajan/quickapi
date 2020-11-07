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

$router->post('/login', 'V1\Common\Provider\ProviderAuthController@login');
$router->post('/verify', 'V1\Common\Provider\ProviderAuthController@verify');
$router->post('/send-otp', 'V1\Common\CommonController@sendOtp');
$router->post('/verify-otp', 'V1\Common\CommonController@verifyOtp');

$router->post('/signup', 'V1\Common\Provider\ProviderAuthController@signup');

$router->post('/refresh', 'V1\Common\Provider\ProviderAuthController@refresh');
$router->post('/forgot/otp', 'V1\Common\Provider\ProviderAuthController@forgotPasswordOTP');
$router->post('/reset/otp', 'V1\Common\Provider\ProviderAuthController@resetPasswordOTP');


$router->post('countries', 'V1\Common\Provider\HomeController@countries');

$router->post('cities/{id}', 'V1\Common\Provider\HomeController@cities');

$router->post('/updatelocation', 'V1\Common\Provider\HomeController@updatelocation');

$router->group(['middleware' => 'auth:provider'], function($app) {


    //address
    $app->post('/address/add', 'V1\Common\Provider\HomeController@addmanageaddress');
    $app->get('/provideraddress', 'V1\Common\Provider\HomeController@listmanageaddress');
    $app->delete('/address/{id}', 'V1\Common\Provider\HomeController@deletemanageaddress');
    $app->get('/address/{id}', 'V1\Common\Provider\HomeController@editmanageaddress');

    $app->post('/logout', 'V1\Common\Provider\ProviderAuthController@logout');

    $app->get('/chat', 'V1\Common\Provider\HomeController@get_chat');

    $app->get('/check/request', 'V1\Common\Provider\HomeController@index');

    $app->post('/accept/request', 'V1\Common\Provider\HomeController@accept_request');

    $app->post('/updatelocation/airport', 'V1\Common\Provider\HomeController@update_location_airport');

    // $app->get('/check/serve/request', 'V1\Service\Provider\ServeController@index');

    $app->post('/cancel/request', 'V1\Common\Provider\HomeController@cancel_request');

    $app->post('/listdocuments', 'V1\Common\Provider\ProviderAuthController@listdocuments');

    $app->post('/documents', 'V1\Common\Provider\ProviderAuthController@document_store');
    
    $app->get('/profile', 'V1\Common\Provider\HomeController@show_profile');
    $app->post('/profile', 'V1\Common\Provider\HomeController@update_profile');
    $app->post('/password', 'V1\Common\Provider\HomeController@password_update');

    $app->post('/card', 'V1\Common\Provider\HomeController@addcard');
    $app->get('card', 'V1\Common\Provider\HomeController@carddetail');
    $app->get('list', 'V1\Common\Provider\HomeController@providerlist');
    $app->delete('card/{id}', 'V1\Common\Provider\HomeController@deleteCard');
    $app->get('/walletamount', 'V1\Common\User\HomeController@walletamount');
    $app->post('/add/money', 'V1\Common\PaymentController@add_money');
    $app->get('/payment/response', 'V1\Common\Provider\PaymentController@response');
    $app->get('/payment/failure', 'V1\Common\Provider\PaymentController@failure');
    $app->get('/wallet', 'V1\Common\Provider\HomeController@walletlist');
    $app->get('services/list', 'V1\Common\Provider\HomeController@provider_services');
    

    $app->get('/vehicle', 'V1\Common\Provider\HomeController@vehicle_list');
    $app->post('/vehicle', 'V1\Common\Provider\HomeController@add_vehicle');
    $app->post('/vechile/add', 'V1\Common\Provider\HomeController@addvechile');
    $app->post('/vehicle/edit', 'V1\Common\Provider\HomeController@editvechile');
    $app->delete('providerdocument/{id}', 'V1\Common\Provider\HomeController@deleteproviderdocument');
    $app->get('/services_status/{id}', 'V1\Common\Provider\HomeController@services_status');

    $app->post('/service', 'V1\Common\Provider\HomeController@add_service');
    $app->get('/orderstatus', 'V1\Common\Provider\HomeController@order_status');
    $app->post('/vechile/addservice', 'V1\Common\Provider\HomeController@addproviderservice');
    $app->post('/vechile/editservice', 'V1\Common\Provider\HomeController@editproviderservice');
    $app->get('/reasons', 'V1\Common\Provider\HomeController@reasons');
    $app->post('/updatelanguage', 'V1\Common\Provider\HomeController@updatelanguage');
    $app->get('/adminservices', 'V1\Common\Provider\HomeController@adminservices');
    $app->get('/notification', 'V1\Common\Provider\HomeController@notification');
    $app->get('/bankdetails/template', 'V1\Common\Provider\HomeController@template');
    $app->post('/addbankdetails', 'V1\Common\Provider\HomeController@addbankdetails');
    $app->post('/editbankdetails', 'V1\Common\Provider\HomeController@editbankdetails');
    $app->post('/referemail', 'V1\Common\Provider\HomeController@refer_email');
    $app->post('/defaultcard', 'V1\Common\Provider\HomeController@defaultcard');
    $app->get('/onlinestatus/{id}', 'V1\Common\Provider\HomeController@onlinestatus');
    $app->get('/earnings/{id}', 'V1\Common\Provider\HomeController@totalEarnings');
    $app->post('/wallet/transfer', 'V1\Common\Provider\HomeController@wallet_transfer');
    $app->get('/providers', function() {
        return response()->json([
            'message' => \Auth::guard('provider')->user(), 
        ]);
    });
	$app->post('device_token', 'V1\Common\Provider\HomeController@updateDeviceToken');
});

$router->post('/clear', 'V1\Common\Provider\HomeController@clear');