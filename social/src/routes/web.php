<?php

// Merchants
$route = env('PACKAGE_ROUTE', '').'/social_signins/';
$controller = 'Increment\Account\Social\Http\SocialController@';
Route::post($route.'auth', $controller."auth");
Route::get($route.'test', $controller."test");
