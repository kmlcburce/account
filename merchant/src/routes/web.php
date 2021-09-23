<?php

// Merchants
$route = env('PACKAGE_ROUTE', '').'/merchants/';
$controller = 'Increment\Account\Merchant\Http\MerchantController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");
