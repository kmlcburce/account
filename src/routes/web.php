<?php

// Billing Information
$route = env('PACKAGE_ROUTE', '').'/billing_informations/';
$controller = 'Increment\Account\Http\BillingInformationController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Account Information
$route = env('PACKAGE_ROUTE', '').'/account_informations/';
$controller = 'Increment\Account\Http\AccountInformationController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_account_info', $controller."retrieveAccountInfo");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Account
$route = env('PACKAGE_ROUTE', '').'/accounts/';
$controller = 'Increment\Account\Http\AccountController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_accounts', $controller.'retrieveAccounts');
Route::post($route.'retrieve_account_profile', $controller.'retrieveAccountProfile');
Route::post($route.'update', $controller."update");
Route::post($route.'update_verification', $controller."updateByVerification");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");
Route::post($route.'mail',  $controller."testMail");
Route::post($route.'verify', $controller."verify");
Route::post($route.'request_reset',  $controller."requestReset");
Route::post($route.'update_password', $controller.'updatePassword');
Route::post($route.'update_email', $controller.'updateEmail');
Route::post($route.'update_type', $controller.'updateType');
Route::post($route.'update_account_type', $controller.'updateAccountType');
Route::post($route.'accounts_count', $controller.'accountTypeSize');
Route::post($route.'update_last_log_in', $controller.'updateLastLogin');
Route::post($route.'retrieve_type_size', $controller.'getAccountTypeSize');
Route::post($route.'retrieve_pending_verified', $controller.'getAccountPending');
Route::post($route.'social_login', $controller.'updateTokenByEmail');

// Account Profile
$route = env('PACKAGE_ROUTE', '').'/account_profiles/';
$controller = 'Increment\Account\Http\AccountProfileController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");

// Account Online Status
$route = env('PACKAGE_ROUTE', '').'/account_online_status/';
$controller = 'Increment\Account\Http\AccountOnlineController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


// Sub Accounts
$route = env('PACKAGE_ROUTE', '').'/sub_accounts/';
$controller = 'Increment\Account\Http\SubAccountController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");
Route::post($route.'retrieve_by_filter', $controller."retrieveAll");

// Account Set Types
$route = env('PACKAGE_ROUTE', '').'/account_set_types/';
$controller = 'Increment\Account\Http\AccountSetTypeController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");


// Account Set Types
$route = env('PACKAGE_ROUTE', '').'/login_attempts/';
$controller = 'Increment\Account\Http\LoginAttemptController@';
Route::post($route.'create', $controller."create");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'update', $controller."update");
Route::post($route.'delete', $controller."delete");
Route::get($route.'test', $controller."test");
