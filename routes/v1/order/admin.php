	<?php

$router->group(['middleware' => 'auth:admin'], function ($app) {
    $app->group(['prefix'=>'store'], function($app){
        // Store Type
$app->get('/storetypes', 'V1\Order\Admin\Resource\StoretypeController@index');

$app->post('/storetypes', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\StoretypeController@store']);

$app->get('/storetypes/{id}', 'V1\Order\Admin\Resource\StoretypeController@show');

$app->patch('/storetypes/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\StoretypeController@update']);

$app->delete('/storetypes/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\StoretypeController@destroy']);

$app->get('/storetypelist', 'V1\Order\Admin\Resource\StoretypeController@storetypelist'); 

$app->get('/storetypes/{id}/updateStatus', 'V1\Order\Admin\Resource\StoretypeController@updateStatus');

$app->get('/orderdocuments/{id}', 'V1\Order\Admin\Resource\StoretypeController@webproviderservice');

$app->get('/pricing/{store_type_id}/{city_id}', 'V1\Order\Admin\Resource\StoretypeController@getstorePrice');

$app->post('/pricings', 'V1\Order\Admin\Resource\StoretypeController@storePricePost');

// Drugs
$app->get('/druglist', 'V1\Order\Admin\Resource\DrugController@drugList'); 

$app->get('/drug', 'V1\Order\Admin\Resource\DrugController@index');

$app->post('/drugs', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\DrugController@store']);

$app->get('/drugs/{id}', 'V1\Order\Admin\Resource\DrugController@show'); 

$app->patch('/drugs/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\DrugController@update']); 

$app->delete('/drugs/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\DrugController@destroy']);
$app->get('/drugs/{id}/updateStatus', 'V1\Order\Admin\Resource\DrugController@updateStatus');

// Brand

$app->get('/brand', 'V1\Order\Admin\Resource\BrandController@index');

$app->post('/brands', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\BrandController@store']);

$app->get('/brands/{id}', 'V1\Order\Admin\Resource\BrandController@show'); 

$app->patch('/brands/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\BrandController@update']); 

$app->delete('/brands/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\BrandController@destroy']);

$app->get('/brands/{id}/updateStatus', 'V1\Order\Admin\Resource\BrandController@updateStatus');

// Attriputes
$app->get('/attribute', 'V1\Order\Admin\Resource\AttributeController@index');

$app->post('/attributes', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\AttributeController@store']);

$app->get('/attributes/{id}', 'V1\Order\Admin\Resource\AttributeController@show'); 

$app->patch('/attributes/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\AttributeController@update']); 

$app->get('/attribute_values/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\AttributeController@destroy']);

$app->get('/attributes/{id}/updateStatus', 'V1\Order\Admin\Resource\AttributeController@updateStatus');


// Cuisines

$app->get('/cuisines', 'V1\Order\Admin\Resource\CuisinesController@index');

$app->post('/cuisines', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\CuisinesController@store']);

$app->get('/cuisines/{id}', 'V1\Order\Admin\Resource\CuisinesController@show'); 

$app->patch('/cuisines/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\CuisinesController@update']); 

$app->delete('/cuisines/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\CuisinesController@destroy']);

$app->get('/cuisinelist/{id}', 'V1\Order\Admin\Resource\CuisinesController@cuisinelist');

$app->get('/cuisines/{id}/updateStatus', 'V1\Order\Admin\Resource\CuisinesController@updateStatus');

//Shops
$app->get('/shops', 'V1\Order\Admin\Resource\ShopsController@index');

$app->post('/shops', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsController@store']);

$app->get('/shops/{id}', 'V1\Order\Admin\Resource\ShopsController@show');

$app->patch('/shops/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsController@update']);

$app->delete('/shops/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsController@destroy']);

$app->get('/shops/{id}/updateStatus', 'V1\Order\Admin\Resource\ShopsController@updateStatus');

$app->get('/shops/wallet/{id}', 'V1\Order\Admin\Resource\ShopsController@walletDetails');

$app->get('shops/storelogs/{id}', 'V1\Order\Admin\Resource\ShopsController@logDetails');

$app->get('/get-store-price', 'V1\Order\Admin\Resource\ShopsController@getStorePriceCities');

//Shops Add on
$app->get('/addon/{id}', 'V1\Order\Admin\Resource\ShopsaddonController@index');

$app->post('/addons', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsaddonController@store']);

$app->get('/addons/{id}', 'V1\Order\Admin\Resource\ShopsaddonController@show');

$app->patch('/addons/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsaddonController@update']);

$app->delete('/addons/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsaddonController@destroy']);

$app->get('/addonslist/{store_id}', 'V1\Order\Admin\Resource\ShopsaddonController@addonlist');
$app->get('/addonslist/{store_id}/{item_id}', 'V1\Order\Admin\Resource\ShopsaddonController@addonlist');

$app->get('/addon/{id}/updateStatus', 'V1\Order\Admin\Resource\ShopsaddonController@updateStatus');

//Shops Category
$app->get('/categoryindex', 'V1\Order\Admin\Resource\ShopscategoryController@index');

$app->post('/category', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopscategoryController@store']);

$app->get('/category/{id}', 'V1\Order\Admin\Resource\ShopscategoryController@show');

$app->patch('/category/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopscategoryController@update']);

$app->delete('/category/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopscategoryController@destroy']);

$app->get('/categorylist', 'V1\Order\Admin\Resource\ShopscategoryController@allCategoryList');

$app->get('/category/{id}/updateStatus', 'V1\Order\Admin\Resource\ShopscategoryController@updateStatus');

$app->get('/categories-list', 'V1\Order\Admin\Resource\ShopscategoryController@allCategoryList');

//Shpos Items

$app->get('/itemsindex/{id}', 'V1\Order\Admin\Resource\ShopsitemsController@index');

$app->post('/items', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsitemsController@store']);

$app->get('/items/{id}', 'V1\Order\Admin\Resource\ShopsitemsController@show');

$app->patch('/items/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsitemsController@update']);

$app->delete('/items/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\ShopsitemsController@destroy']);

$app->get('/units', 'V1\Order\Admin\Resource\ShopsitemsController@unit');

$app->get('/brands', 'V1\Order\Admin\Resource\ShopsitemsController@Brand');
$app->get('/attributes', 'V1\Order\Admin\Resource\ShopsitemsController@attributes');
$app->get('/attributelist/{id}', 'V1\Order\Admin\Resource\ShopsitemsController@attributeValue');

$app->get('/items/{id}/updateStatus', 'V1\Order\Admin\Resource\ShopsitemsController@updateStatus');
//request history
$app->get('/requesthistory', 'V1\Order\User\HomeController@requestHistory');

$app->get('/requestschedulehistory', 'V1\Order\User\HomeController@requestScheduleHistory');

$app->get('/requesthistory/{id}', 'V1\Order\User\HomeController@requestHistoryDetails');

$app->get('/requestStatementhistory', 'V1\Order\User\HomeController@requestStatementHistory');

$app->get('/storeStatementHistory', 'V1\Order\Admin\Resource\ShopsController@storeStatementHistory');

$app->get('/items/{id}/updateStatus', 'V1\Order\Admin\Resource\ShopsitemsController@updateStatus'); 

$app->get('/items/{id}/updateStatus', 'V1\Order\Admin\Resource\ShopsitemsController@updateStatus'); 

//shop Dispute
$app->post('dispute-order-search', 'V1\Order\Admin\Resource\StoreDisputeController@searchOrderDispute');

$app->get('/requestdispute', 'V1\Order\Admin\Resource\StoreDisputeController@index'); 

$app->post('/requestdispute', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\StoreDisputeController@store']);

$app->get('/requestdispute/{id}', 'V1\Order\Admin\Resource\StoreDisputeController@show');

$app->patch('/requestdispute/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\StoreDisputeController@update']);

$app->get('disputelist', 'V1\Order\Admin\Resource\StoreDisputeController@dispute_list');

$app->get('findprovider/{store_id}', 'V1\Order\Admin\Resource\StoreDisputeController@findprovider');

//dashboard
$app->get('/dashboards/{id}', 'V1\Order\Admin\Resource\ShopsController@dashboarddata');	

$app->get('/Storedashboard/{id}', 'V1\Order\Admin\Resource\ShopsController@storedashboard');	

});

$app->get('getordercity', 'V1\Order\Admin\Resource\StoretypeController@getcity');	




  // SERVICE MAIN CATEGORIES
    $app->get('/health/categories', 'V1\Order\Admin\Resource\HealthCategoryController@index');

    $app->post('/health/categories', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthCategoryController@store']);

    $app->get('/health/categories/{id}', 'V1\Order\Admin\Resource\HealthCategoryController@show');

    $app->patch('/health/categories/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthCategoryController@update']);

    $app->delete('/health/categories/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthCategoryController@destroy']);

    $app->get('/health/categories/{id}/updateStatus', 'V1\Order\Admin\Resource\HealthCategoryController@updateStatus');

    // SERVICE SUB CATEGORIES
    $app->get('/health/categories-list', 'V1\Order\Admin\Resource\HealthSubCategoryController@categoriesList');

    $app->get('/health/subcategories', 'V1\Order\Admin\Resource\HealthSubCategoryController@index');

    $app->post('/health/subcategories', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthSubCategoryController@store']);

    $app->get('/health/subcategories/{id}', 'V1\Order\Admin\Resource\HealthSubCategoryController@show');

    $app->patch('/health/subcategories/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthSubCategoryController@update']);

    $app->delete('/health/subcategories/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthSubCategoryController@destroy']);

    $app->get('/health/subcategories/{id}/updateStatus', 'V1\Order\Admin\Resource\HealthSubCategoryController@updateStatus');

    // SERVICES
    $app->get('/health/subcategories-list/{categoryId}', 'V1\Order\Admin\Resource\HealthController@subcategoriesList');

    $app->get('/health/listing', 'V1\Order\Admin\Resource\HealthController@index');

    $app->post('/health/listing', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthController@store']);

    $app->get('/health/listing/{id}', 'V1\Order\Admin\Resource\HealthController@show');

    $app->patch('/health/listing/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthController@update']);

    $app->delete('/health/listing/{id}', ['middleware' => 'demo', 'uses' => 'V1\Order\Admin\Resource\HealthController@destroy']);

    $app->get('/health/listing/{id}/updateStatus', 'V1\Order\Admin\Resource\HealthController@updateStatus');

});