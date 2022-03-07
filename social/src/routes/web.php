<?php

// Merchants
$route = env('PACKAGE_ROUTE', '').'/socials/';
$controller = 'Increment\Account\Social\Http\Controller@';
Route::post($route.'create', $controller."create");
Route::post($route.'auth', $controller."auth");
Route::get($route.'test', $controller."test");
