<?php

$router->group(['middleware' => 'auth:admin'], function ($app) {

		// vehile
	$app->get('/delivery_type', 'V1\Common\Admin\Resource\ProviderController@deliverytype');
    $app->get('/deliveryvehicle', 'V1\Delivery\Admin\DeliveryVehicleController@index');
    $app->get('/deliveryvehicle-list', 'V1\Delivery\Admin\DeliveryVehicleController@vehicleList');
    $app->get('/getdeliveryvehicletype', 'V1\Delivery\Admin\DeliveryVehicleController@getvehicletype');

		$app->post('/deliveryvehicle', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\DeliveryVehicleController@store']);

		$app->get('/deliveryvehicle/{id}', 'V1\Delivery\Admin\DeliveryVehicleController@show');

		$app->patch('/deliveryvehicle/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\DeliveryVehicleController@update']);

		$app->post('/deliveryvehicle/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\DeliveryVehicleController@destroy']);

		$app->get('/delivery/price/get/{id}', 'V1\Delivery\Admin\DeliveryVehicleController@gettaxiprice');

		$app->get('/deliveryvehicle/{id}/updateStatus', 'V1\Delivery\Admin\DeliveryVehicleController@updateStatus');

		$app->get('/delivery/comission/{country_id}/{city_id}/{admin_service_id}', 'V1\Delivery\Admin\DeliveryVehicleController@getComission');
		
		$app->get('/gettaxiprice/{id}', 'V1\Delivery\Admin\DeliveryVehicleController@gettaxiprice');

		$app->post('/delivery/track/request', 'V1\Delivery\User\DeliveryController@track_location');
		

		$app->post('/deliveryprice', 'V1\Delivery\Admin\DeliveryVehicleController@rideprice');

		$app->get('/deliveryprice/{delivery_vehicle_id}/{city_id}', 'V1\Delivery\Admin\DeliveryVehicleController@getRidePrice');

		$app->post('/delivery/comission', 'V1\Delivery\Admin\DeliveryVehicleController@comission');


		$app->get('usersearch', 'V1\Delivery\User\DeliveryController@search_user');

		$app->get('userprovider', 'V1\Delivery\User\DeliveryController@search_provider');

		$app->post('ridesearch', 'V1\Delivery\User\DeliveryController@searchRideLostitem');

		$app->post('disputeridesearch', 'V1\Delivery\User\DeliveryController@searchRideDispute');


		// Ride Request Dispute
		$app->get('/requestdispute', 'V1\Delivery\Admin\RideRequestDisputeController@index');

		$app->post('/requestdispute', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\RideRequestDisputeController@store']);

		$app->get('/requestdispute/{id}', 'V1\Delivery\Admin\RideRequestDisputeController@show');

		$app->patch('/requestdispute/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\RideRequestDisputeController@update']);

		$app->get('disputelist', 'V1\Delivery\Admin\RideRequestDisputeController@dispute_list');
				
		// request history
		$app->get('/delivery/requesthistory', 'V1\Delivery\User\DeliveryController@requestHistory');
		$app->get('/delivery/requestschedulehistory', 'V1\Delivery\User\DeliveryController@requestscheduleHistory');
		$app->get('/delivery/requesthistory/{id}', 'V1\Delivery\User\DeliveryController@requestHistoryDetails');
		$app->get('/delivery/requestStatementhistory', 'V1\Delivery\User\DeliveryController@requestStatementHistory');

		// vehicle type
		$app->get('/deliverytype', 'V1\Delivery\Admin\DeliveryTypeController@index');

		$app->post('/deliverytype', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\DeliveryTypeController@store']);

		$app->get('/deliverytype/{id}', 'V1\Delivery\Admin\DeliveryTypeController@show');

		$app->patch('/deliverytype/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\DeliveryTypeController@update']);

		$app->post('/deliverytype/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\DeliveryTypeController@destroy']);

		$app->get('/deliverytype/{id}/updateStatus', 'V1\Delivery\Admin\DeliveryTypeController@updateStatus');
		$app->get('/deliverydocuments/{id}', 'V1\Delivery\Admin\DeliveryTypeController@webproviderservice');

		// package type
		$app->get('/packagetype', 'V1\Delivery\Admin\PackageTypeController@index');

		$app->post('/packagetype', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\PackageTypeController@store']);

		$app->get('/packagetype/{id}', 'V1\Delivery\Admin\PackageTypeController@show');

		$app->patch('/packagetype/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\PackageTypeController@update']);

		$app->post('/packagetype/{id}', ['middleware' => 'demo', 'uses' => 'V1\Delivery\Admin\PackageTypeController@destroy']);

		$app->get('/packagetype/{id}/updateStatus', 'V1\Delivery\Admin\PackageTypeController@updateStatus');

		// statement
		$app->get('/statement', 'V1\Delivery\User\DeliveryController@statement');

		// Dashboard

		$app->get('deliverydashboard/{id}', 'V1\Delivery\Admin\RideRequestDisputeController@dashboarddata');

		 $app->get('getdeliverycity', 'V1\Delivery\Admin\VehicleController@getcity');



});
