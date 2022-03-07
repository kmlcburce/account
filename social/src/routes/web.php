<?php

// Merchants
$route = env('PACKAGE_ROUTE', '').'/socials/';
$controller = 'Increment\Account\Social\Http\Controller@';
Route::get($route.'test', $controller."test");
