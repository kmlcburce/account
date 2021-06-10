<?php

// Plan
$route = env('PACKAGE_ROUTE', '').'/plans/';
$controller = 'Increment\Account\Plan\Http\PlanController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::post($route.'test', $controller."getByParamsScope");


// Plan
$route = env('PACKAGE_ROUTE', '').'/plan_histories/';
$controller = 'Increment\Account\Plan\Http\PlanHistoryController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

