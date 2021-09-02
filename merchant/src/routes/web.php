<?php

// Merchants
$route = env('PACKAGE_ROUTE', '').'/merchants/';
$controller = 'Increment\Imarket\Merchant\Http\MerchantController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve_merchants', $controller."retrieveMerchants");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_all', $controller."retrieveAll");
Route::post($route.'retrieve_with_rating', $controller."retriveMerchantWithRating");
Route::post($route.'update', $controller."update");
Route::post($route.'update_by_verification', $controller."updateByVerification");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");
