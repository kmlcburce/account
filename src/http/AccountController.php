<?php

namespace Increment\Account\Http;

use Increment\Account\Models\Account;
use Increment\Account\Models\AccountInformation;
use Increment\Account\Models\BillingInformation;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountController extends APIController
{

  public $accountCardController = 'App\Http\Controllers\AccountCardController';
  public $cacheController = 'Increment\Common\Cache\Http\CacheController';

    function __construct(){
      $this->model = new Account();
      $this->validation = array(  
        "email" => "unique:accounts",
        "username"  => "unique:accounts"
      );
      $this->notRequired = array(
        'token',
        "password",
        "email"
      );
    }

    public function create(Request $request){
     $request = $request->all();
     $referralCode = $request['referral_code'];
     $invitationPassword = $request['password'];
     $dataAccount = array(
      'code'  => $this->generateCode(),
      'password'        => $request['password'] !== null ? Hash::make($request['password']) : "",
      'status'          => isset($request['account_status']) ? $request['account_status'] :'NOT_VERIFIED',
      'email'           => $request['email'],
      'username'        => $request['username'],
      'account_type'    => $request['account_type'],
      'token'           => isset($request['token']) ? $request['token'] : null,
      'created_at'      => Carbon::now()
     );
     if(isset($request['token'])){
       $dataAccount['token'] = $request['token'];
     }
     $this->model = new Account();
     $this->insertDB($dataAccount, true);
     $accountId = $this->response['data'];

     if($accountId){
       $this->createDetails($accountId, $request['account_type']);
       // app('Increment\Plan\Http\InvitationController')->createWithValidationParams($accountId, $request['email']);

       // //send email verification here
       // if($referralCode != null){
       //    // app('Increment\Plan\Http\InvitationController')->confirmReferral($referralCode);
       // }
       if(env('SUB_ACCOUNT') == true){
          $status = $request['status'];
          if($status == 'ADMIN'){
            app('Increment\Account\Http\SubAccountController')->createByParams($accountId, $accountId, $status);
          }
          if($status != 'ADMIN'){
            app('Increment\Account\Http\SubAccountController')->createByParams($request['account_id'], $accountId, $status);
            app('App\Http\Controllers\EmailController')->loginInvitation($accountId, $invitationPassword);
          }
          app('App\Http\Controllers\EmailController')->verification($accountId);
       }
     }
     return $this->response();
    }

    public function createSubAccount(Request $request){
       $request = $request->all();
       
       $invitationPassword = $request['password'];
       
       $dataAccount = array(
        'code'  => $this->generateCode(),
        'password'        => $request['password'] !== null ? Hash::make($request['password']) : "",
        'status'          => isset($request['account_status']) ? $request['account_status'] :'NOT_VERIFIED',
        'email'           => $request['email'],
        'username'        => $request['username'],
        'account_type'    => $request['account_type'],
        'token'           => isset($request['token']) ? $request['token'] : null,
        'created_at'      => Carbon::now()
       );
       
       if(isset($request['token'])){
         $dataAccount['token'] = $request['token'];
       }

       $this->model = new Account();
       $this->insertDB($dataAccount, true);
       $accountId = $this->response['data'];

       if($accountId){
        $this->createDetailsSubAccount($accountId, $request['account_type'], $request['first_name'], $request['last_name']);
        app('Increment\Account\Http\SubAccountController')->createByParamsWithDetails($request['account_id'], $accountId, 'USER', $request['details']);
        app('App\Http\Controllers\EmailController')->loginInvitation($accountId, $invitationPassword);
       }
       return $this->response();
    }

    public function createDetailsSubAccount($accountId, $type, $firstName, $lastName){
      $info = new AccountInformation();
      $info->account_id = $accountId;
      $info->first_name = $firstName;
      $info->last_name = $lastName;
      $info->created_at = Carbon::now();
      $info->save();

      $billing = new BillingInformation();
      $billing->account_id = $accountId;
      $billing->created_at = Carbon::now();
      $billing->save();
      if(env('NOTIFICATION_SETTING_FLAG') == true){
        app('App\Http\Controllers\NotificationSettingController')->insert($accountId);
      }
    }

    public function createAccount($request){
      $referralCode = $request['referral_code'];
      $invitationPassword = $request['password'];
      $dataAccount = array(
       'code'  => $this->generateCode(),
       'password'        => $request['password'] !== null ? Hash::make($request['password']) : "",
       'status'          => 'NOT_VERIFIED',
       'email'           => $request['email'],
       'username'        => $request['username'],
       'account_type'    => $request['account_type'],
       'token'           => isset($request['socialToken']) ? json_encode(array(
         'token' => $request['socialToken']
       )): null,
       'created_at'      => Carbon::now()
      );
      // if(isset($request['socialToken'])){
      //   $dataAccount['token'] = $request['socialToken'];
      // }
      $this->model = new Account();
      $this->insertDB($dataAccount, true);
      $accountId = $this->response['data'];
 
      if($accountId){
        $this->createDetails($accountId, $request['account_type']);
        //send email verification here
        if($referralCode != null){
          app('Increment\Plan\Http\InvitationController')->confirmReferral($referralCode);
        }
        if(env('SUB_ACCOUNT') == true){
           $status = $request['status'];
           if($status == 'ADMIN'){
             app('Increment\Account\Http\SubAccountController')->createByParams($accountId, $accountId, $status);
           }
           if($status != 'ADMIN'){
             app('Increment\Account\Http\SubAccountController')->createByParams($request['account_id'], $accountId, $status);
             app('App\Http\Controllers\EmailController')->loginInvitation($accountId, $invitationPassword);
           }
           app('App\Http\Controllers\EmailController')->verification($accountId);
        }
      }
      return $this->response();
     }

    public function createDetails($accountId, $type){
      $info = new AccountInformation();
      $info->account_id = $accountId;
      $info->created_at = Carbon::now();
      $info->save();

      $billing = new BillingInformation();
      $billing->account_id = $accountId;
      $billing->created_at = Carbon::now();
      $billing->save();
      if(env('NOTIFICATION_SETTING_FLAG') == true){
        app('App\Http\Controllers\NotificationSettingController')->insert($accountId);
      }
    }

    public function generateCode(){
      $code = 'acc_'.substr(str_shuffle($this->codeSource), 0, 60);
      $codeExist = Account::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function verify(Request $request){
      $data = $request->all();
      $this->model = new Account();
      $this->retrieveDB($data);
      if(sizeof($this->response['data']) > 0){
        app('App\Http\Controllers\EmailController')->directVerification($this->response['data'][0]['id']);  
      }
      return $this->response();
    }

    public function updateByVerification(Request $request){
      if($this->checkAuthenticatedUser(true) == false){
        return $this->response();
      }
      $data = $request->all();
      $result = Account::where('id', '=', $data['id'])->update(array(
        'status' => $data['status']
      ));
      $this->response['data'] = $result ? true : false;
      $details = null;
      if($this->response['data'] == true){
        if($data['status'] == 'ACCOUNT_VERIFIED'){
          $details = 'your account is already verified. You can now start creating request but if you want to earn while using Payhiram, Be our Partner. What are you waiting for? Apply Now!';
        }else if($data['status'] == 'BASIC_VERIFIED'){
          $details = 'you are now an official Payhiram partner. Enjoy earning everyday with the maximum amount of 10,000 pesos. We are happy and excited to be part of your source of income.';
        }else if($data['status'] == 'STANDARD_VERIFIED'){
          $details = 'you are now an official Payhiram partner. Enjoy earning everyday with the maximum amount of 50,000 pesos. We are happy and excited to be part of your source of income.';
        }else if($data['status'] == 'BUSINESS_VERIFIED'){
          $details = 'you are now an official Payhiram partner. Enjoy earning everyday with the maximum amount of 100,000 pesos. We are happy and excited to be part of your source of income.';
        }else if($data['status'] == 'ENTERPRISE_VERIFIED'){
          $details = 'you are now an official Payhiram partner. Enjoy earning everyday with the maximum amount of 500,000 pesos. We are happy and excited to be part of your source of income.';
        }else if($data['status'] == 'VERIFIED'){
          $details = 'your email has been verified. To complete your registration, we need a little more information including the completion of your profile details.';
        }else if($data['status'] == 'BLOCKED'){
          $details = `your our account has been blocked. We've detected suspicious activity on your Payhiram Account and have locked it as a security precaution. If you think this is a mistake, please contact payhiramph@gmail.com.`;
        }
        app('App\Http\Controllers\EmailController')->verification_status($data['id'], $details);
      }

      app($this->cacheController)->delete('account_details_'.$data['id']);
      app($this->cacheController)->delete('user_'.$data['id']);
      return $this->response();
    }

    public function requestReset(Request $request){
      if($this->checkAuthenticatedUser(true) == false){
        return $this->response();
      }
      $data = $request->all();
      $result = Account::where('email', '=', $data['email'])->get();
      if(sizeof($result) > 0){
        app('App\Http\Controllers\EmailController')->resetPassword($result[0]['id']);
        return response()->json(array('data' => true));
      }else{
        return response()->json(array('data' => false));
      }
    }
    
    public function requestResetViaOTP(Request $request){
      if($this->checkAuthenticatedUser(true) == false){
        return $this->response();
      }
      $data = $request->all();
      $result = Account::where('email', '=', $data['email'])->get();
      if(sizeof($result) > 0){
        return response()->json(array('data' => true));
      }else{
        return response()->json(array('data' => false));
      }
    }
    

    public function update(Request $request){
      if($this->checkAuthenticatedUser(true) == false){
        return $this->response();
      }
      $data = $request->all();
      $result = Account::where('code', '=', $data['code'])->where('username', '=', $data['username'])->get();
      if(sizeof($result) > 0){
        $updateData = array(
          'password'  => Hash::make($data['password'])
        );
        $updateResult = Account::where('id', '=', $result[0]['id'])->update($updateData);
        if($updateResult == true){
          $this->response['data'] = true;
          app('App\Http\Controllers\EmailController')->changedPassword($result[0]['id']);
          return $this->response();
        }else{
          return response()->json(array('data' => false));
        }
      }else{
        return response()->json(array('data' => false));
      }
    }


    public function updatePassByEmail(Request $request){
      if($this->checkAuthenticatedUser(true) == false){
        return $this->response();
      }
      $data = $request->all();
      $id = $this->retrieveByEmail($data['email']);
      $result = Account::where('email', '=', $data['email'])->get();
      if(sizeof($result) > 0){
        $updateData = array(
          'password'  => Hash::make($data['password'])
        );
        $updateResult = Account::where('id', '=', $id['id'])->update($updateData);
        if($updateResult == true){
          $this->response['data'] = true;
          app('App\Http\Controllers\EmailController')->changedPassword($id['id']);
          return $this->response();
        }else{
          return response()->json(array('data' => false));
        }
      }else{
        return response()->json(array('data' => false));
      }
    }

    public function updatePasswordViaSubAccount($accountId, $password){
      $invitationPassword = $password;
      $password = Hash::make($password);
      Account::where('id', '=', $accountId)->update(array(
        'password' => $password
      ));
      app('App\Http\Controllers\EmailController')->loginInvitation($accountId, $invitationPassword);
      return true;
    }
    public function hashPassword($password){
      $data['password'] = Hash::make($password);
      return $data;
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new Account();
      $result = $this->retrieveDB($data);
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i] = $this->retrieveDetailsOnLogin($result[$i]);
          $i++;
        }
        return response()->json(array('data' => $result));
      }else{
        return $this->response();
      }
    }

    public function retrieveAccountAdmin(Request $request){
      $data = $request->all();
      $con = $data['condition'];
      $this->model = new Account();
      $result = $this->retrieveDB($data);
      $size = Account::where($con[0]['column'], $con[0]['clause'], $con[0]['value'])->where($con[1]['column'], $con[1]['clause'], $con[1]['value'])->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i] = $this->retrieveDetailsOnLogin($result[$i]);
          $i++;
        }
        return response()->json(array('data' => $result, 'size' => sizeof($size)));
      }else{
        return $this->response();
      }
    }

    public function retrieveAccountProfile(Request $request){
      $data = $request->all();
      $result = Account::where('id', '=', $data['account_id'])->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i] =  $this->retrieveProfileDetails($key->id);
        }
      }
      return response()->json(array('data' => $result));
    }


    public function retrieveAccounts(Request $request){
      $data = $request->all();
      if(isset($data['accountType'])){
        $con = $data['condition'];
        $result = Account::where('account_type', '=', $data['accountType'])->where($con[0]['column'], $con[0]['clause'], $con[0]['value'])->limit($data['limit'])
          ->offset($data['offset'])->orderBy(array_keys($data['sort'])[0], array_values($data['sort'])[0])->get();
      }else{
        $this->model = new Account();
        $result = $this->retrieveDB($data);
      }
      if(!$result){
        $this->response['data'] = [];
        return $this->response();
      }
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i] = $this->retrieveAppDetails($result[$i], $result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['id']);
          $this->response['data'][$i]['card'] = app($this->accountCardController)->getAccountCard($result[$i]['id']);
          $this->response['data'][$i]['rating'] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload('account', $result[$i]['id']);
          $this->response['data'][$i]['partner_locations'] = null;
          if(env('PARTNER_LOCATIONS') == true){
            $this->response['data'][$i]['partner_locations'] = app(
              'App\Http\Controllers\InvestorLocationController')->getByParams('account_id', $result[$i]['id']);
          }
          $i++;
        }
      }
      if(isset($data['condition'])){
        $condition = $data['condition'];
        if(sizeof($condition) == 1){
          $con = $condition[0];
          $this->response['size'] = Account::where('deleted_at', '=', null)->where($con['column'], $con['clause'], $con['value'])->count();
        }
        if(sizeof($condition) == 2){
          $con = $condition[0];
          $con1 = $condition[1];
          if($con1['clause'] != 'or'){
            $this->response['size'] = Account::where('deleted_at', '=', null)->where($con['column'], $con['clause'], $con['value'])->where($con1['column'], $con1['clause'], $con1['value'])->count();
          }else{
            $this->response['size'] = Account::where('deleted_at', '=', null)->where($con['column'], $con['clause'], $con['value'])->orWhere($con1['column'], '=', $con1['value'])->count();
          }
          
        }
      }else{
        $this->response['size'] = Account::where('deleted_at', '=', null)->count();
      }
      $this->response['data'] = $result;
      return $this->response();
    }

    public function retrieveById($accountId){
      return Account::where('id', '=', $accountId)->get();
    }

    public function getusername($accountId){
      $result = Account::where('id', '=', $accountId)->get(['username']);
      return sizeof($result) > 0 ? $result[0] : null;
    }

    public function getAllowedData($accountId){
      $result = Account::where('id', '=', $accountId)->get(['username', 'email', 'account_type', 'code', 'id', 'status']);
      return sizeof($result) > 0 ? $result[0] : null;
    }

    public function retrieveByEmailAndCode($email, $code){
      return Account::where('code', '=', $code)->where('email', '=', $email)->get();
    }

    public function retrieveByEmail($email){
      $result = Account::where('email', '=', $email)->get();
      return sizeof($result) > 0 ? $result[0] : null;
    }

    public function retrieveByPhone($number){
      $result = AccountInformation::where('cellular_number', '=', $number)->get();
      return sizeof($result) > 0 ? $result[0] : null;
    }

    public function updatePassword(Request $request){ 
      $data = $request->all();
      $data['password'] = Hash::make($data['password']);
      $this->updateDB($data);
      if(env('LOGIN_ATTEMPT_LIMIT') != null){
        // reset here
        app('Increment\Account\Http\LoginAttemptController')->reset(array(
          'account_id'  => $data['id']
        ));
      }
      return $this->response();
    }

    public function updateByParamsByEmail($email, $data){
      return Account::where('email', '=', $email)->update($data); 
    }

    public function updateType(Request $request){ 
      $data = $request->all();
      $this->updateDB($data);
      app('Increment\Account\Http\AccountSetTypeController')->createByParams($data['id']);
      return $this->response();
    }

    public function updateAccountType(Request $request){ 
      $data = $request->all();
      $this->updateDB($data);
      return $this->response();
    }

    public function updateEmail(Request $request){
      if($this->checkAuthenticatedUser() == false){
        return $this->response();
      }
      $request = $request->all();
      $result = Account::where('email', '=', $request['email'])->get();
      $text = array('email' => $request['email']);
      if(sizeof($result) <= 0 && $this->customValidate($text) == true){
        $this->model = new Account();
        $updateData = array(
          'id' => $request['id'],
          'email' => $request['email']
        );
        $this->updateDB($updateData);
        if($this->response['data'] == true){
          $account = Account::where('id', '=', $request['id'])->get();
          return $this->response();
        }else{
          return response()->json(array('data' => false, 'error' => 'Unable to update please contact the support.'));
        }
      }else{
        return response()->json(array('data' => false, 'error' => 'Email already used.'));
      }
    }

    public function customValidate($text){
      $validation = array('email' => 'required|email'); 
      return $this->validateReply($text, $validation);
    }

    public function validateReply($text, $validation){
      $validator = Validator::make($text, $validation);
      if($validator->fails()){
        return false;
      }
      else
        return true;
    }

    public function accountTypeSize(Request $request){
      $count = \DB::table('accounts')
                          ->select('account_type', \DB::raw('count(account_type) as account_count'))
                          ->groupBy('account_type')
                          ->get();
      return response()->json(array('data' => $count));
    }

    public function updateLastLogin(Request $request){
      $data = $request->all();
      $result = Account::where('id', '=', $data['account_id'])->update(array(
        'updated_at' => Carbon::now()
      ));
      $this->response['data'] = $result;
      return $this->response();
    }

    public function getByParamsWithColumns($accountId, $columns){
      $result = Account::where('id', '=', $accountId)->get($columns);
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function getByTokenWithColumns($token, $columns){
      $result = Account::where('token', 'like', '%'.$token.'%')->get($columns);
      return (sizeof($result) > 0) ? $result[0] : null;
    }
    
    public function getAccountIdByParamsWithColumns($code, $columns){
      $result = Account::where('code', '=', $code)->get($columns);
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function getCodeById($id){
      $result = Account::where('id', '=', $id)->get();
      return (sizeof($result) > 0) ? $result[0]['code'] : null;
    }

    public function getAccountTypeSize(){
      $this->response['data'] = array(
        'total_users' => Account::count(),
        'total_verified' => Account::where('status', '=', 'ACCOUNT_VERIFIED')->count(),
        'total_partners' => Account::where('account_type', '=', 'PARTNER')->count(),
        'total_admin' => Account::where('account_type', '=', 'ADMIN')->count()
      );
      return $this->response(); 
    }

    public function getAccountPending(Request $request){
      $data = $request->all();
      if($data['status'] == 'EMAIL_VERIFIED'){
        $ret = DB::table('accounts')
          ->select('*')
          ->where('status', '=', 'EMAIL_VERIFIED')
          ->orderBy('created_at', 'desc')
          ->limit($data['limit'])
          ->get();
      }
      return response()->json(array('data' => $ret));
    }
    
    public function loginSocialAccount(Request $request){
      $data = $request->all();
      $exist = Account::where('email', '=', $data['email'])->orWhere('token', 'like', '%'.$data['socialToken'].'%')->first();
      if($exist !== null){
        $token = json_decode($exist['token']);
        $newToken = array(
          'apple' => isset($token->apple) ? $token->apple : null,
          'token' => isset($token->token) ? $token->token : null,
          'google' => isset($token->google) ? $token->google : null,
          'facebook' => isset($token->facebook) ? $token->facebook : null
        );
        $newToken['token'] = isset($token->token) ? $token->token : null;
        $newToken['google'] = $data['social'] === 'google' ? $data['socialToken'] : $newToken['google'];
        $newToken['apple'] = $data['social'] === 'apple' ? $data['socialToken'] : $newToken['apple'];
        $newToken['facebook'] = $data['social'] === 'facebook' ? $data['socialToken'] : $newToken['facebook'];
        $result = Account::where('email', '=', $data['email'])->orWhere('token', '=', $data['socialToken'])->update(array(
          'token' => json_encode($newToken)
        ));
        $this->response['data'] = $result;
      }else{
        $result = $this->createAccount($data);
        $this->response['data'] = $result;
      }
      return $this->response();
    }
    
    public function retrieveAccountMezzo(Request $request){
      $data = $request->all();
      $con = $data['condition'];
      $result = Account::leftJoin('account_informations as T1', 'T1.account_id', '=', 'accounts.id')
        ->where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
        ->where('account_type', '!=', 'ADMIN')
        ->limit($data['limit'])
        ->offset($data['offset'])
        ->orderBy(array_keys($data['sort'])[0], array_values($data['sort'])[0])
        ->get(['accounts.*', 'T1.first_name', 'T1.last_name', 'cellular_number']);
      
      $size = Account::leftJoin('account_informations as T1', 'T1.account_id', '=', 'accounts.id')
        ->where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
        ->where('account_type', '!=', 'ADMIN')
        ->orderBy(array_keys($data['sort'])[0], array_values($data['sort'])[0])
        ->get();

      for ($i=0; $i <= sizeof($result)-1 ; $i++) { 
        $item = $result[$i];
        $result[$i]['total_bookings'] = app('Increment\Hotel\Reservation\Http\ReservationController')->retrieveTotalReservationsByAccount($item['id']);
        $result[$i]['total_spent'] = app('Increment\Hotel\Reservation\Http\ReservationController')->retrieveTotalSpentByAcccount($item['id']);
        $result[$i]['name'] = $item['first_name'].' '.$item['last_name'];
      }
      $this->response['data'] = $result;
      $this->response['size'] = sizeof($size);
      return $this->response();
    }

    public function createSocialAccount(Request $request){
      $data = $request->all();
      $exist = Account::where('email', '=', $data['email'])->where('username', '=', $data['username'])->first();
      if($exist != null){
        $token = json_decode($exist['token']);
        $newToken = array(
          'apple' => isset($token->apple) ? $token->apple : null,
          'token' => isset($token->token) ? $token->token : null,
          'google' => isset($token->google) ? $token->google : null,
          'facebook' => isset($token->facebook) ? $token->facebook : null
        );
        $newToken['token'] = isset($token->token) ? $token->token : null;
        $newToken['google'] = $data['social'] === 'google' ? $data['socialToken'] : $newToken['google'];
        $newToken['apple'] = $data['social'] === 'apple' ? $data['socialToken'] : $newToken['apple'];
        $newToken['facebook'] = $data['social'] === 'facebook' ? $data['socialToken'] : $newToken['facebook'];
        $update = Account::where('email', '=', $data['email'])->where('username', '=', $data['username'])->update(array(
          'token' => json_encode($newToken),
        ));
        $this->response['data'] = $update !== null ? $exist['id'] : null;
      }else{
        $dataAccount = array(
          'code'  => $this->generateCode(),
          'password'        => $data['password'] !== null ? Hash::make($request['password']) : "",
          'status'          => 'NOT_VERIFIED',
          'email'           => $data['email'],
          'username'        => $data['username'],
          'account_type'    => $data['account_type'],
          'token'           => isset($data['socialToken']) ? json_encode(array(
            'token' => $data['socialToken']
           )) : null,
          'created_at'      => Carbon::now()
         );
        $this->model = new Account();
        $this->insertDB($dataAccount, true);
      }
      $accountId = $this->response['data'];
      if($accountId !== null){
        $this->createDetails($accountId, $request['account_type']);
        $token = Account::where('id', '=', $accountId)->first();
        $returnToken = $token !== null ? json_decode($token['token']) : null;
        $this->response['data'] = $returnToken !== null ? $returnToken->token : null;
      }
      return $this->response();
    }

    public function socialAuthenticate(Request $request){
      $data = $request->all();
      $temp = Account::where('token', 'like', "%".$data['token']."%")->orWhere('email', '=', $data['email'])->first();
      $newToken = array(
        'apple' => null,
        'token' => null,
        'google' => null,
        'facebook' => null
      );
      if($temp !== null){
        if($temp['token'] !== null){
            $decode = json_decode($temp['token']);
            if($decode !== null){
              $newToken['token'] = isset($decode->token) ? $decode->token : null;
              $newToken['google'] = isset($decode->google) ? $decode->google : null;
              $newToken['apple'] = isset($decode->apple) ? $decode->apple : null;
              $newToken['facebook'] = isset($decode->facebook) ? $decode->facebook : null;
              if(isset($decode->token)){
                $this->response['data'] = $decode->token;
                $newToken['token'] = $decode->token;
              }else{
                if(isset($decode->google)){
                  $newToken['google'] = $decode->google;
                  $this->response['data'] = $decode->google;
                }else if(isset($decode->apple)){
                  $newToken['apple'] = $decode->apple;
                  $this->response['data'] = $decode->apple;
                }else if(isset($decode->apple)){
                  $newToken['facebook'] = $decode->facebook;
                  $this->response['data'] -> $decode->facebook;
                }
              }
            }else{
              $newToken['token'] =  $temp['token'];
              $this->response['data'] = $temp['token'];
            }
          }
        }
        Account::where('token', 'like', "%".$data['token']."%")->orWhere('email', '=', $data['email'])->update(array(
          'token' => json_encode($newToken)
        ));
        return $this->response();
      }

    public function checkIfAccountExist(Request $request){
      $data = $request->all();
      if($data['column'] === 'cellular_number'){
        $result = Account::leftJoin('account_informations as T1', 'T1.account_id', '=', 'accounts.id')->where($data['column'], '=', $data['value'])->get('code');
      }else{
        $result = Account::where($data['column'], '=', $data['value'])->get('code');
      }
      return sizeof($result) > 0 ? $result[0] : 'null';
    }

    public function retrieveDashboardAccounts(Request $request){
      $data = $request->all();
      $currDate = Carbon::now();
      $whereArray = array(
       array( function($query)use($data){
          if($data['type'] === 'verified'){
            $query->where('account_type', '=', 'USER')
              ->where('status', '=', 'ACCOUNT_VERIFIED');
          }else{
            $query->where('account_type', '=', $data['type']);
          }
      }));
      $fTransaction = Account::where($whereArray)->first();
      $resDates = [];
      $resData = [];
      if($fTransaction !== null &&  $fTransaction['created_at'] !== null){
        $startDate = new Carbon($fTransaction['created_at']);
        $fTransaction['created_at'] = $startDate->toDateTimeString();
        $dates = [];
        if($data['date'] === 'yearly'){
          $tempYearly = CarbonPeriod::create($fTransaction['created_at'], $currDate->toDateTimeString());
          foreach ($tempYearly as $year) {
            array_push($dates, $year->toDateString());
          }
        }else if($data['date'] === 'current_year'){
          $dates = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        }else if($data['date'] === 'custom'){
        }else if($data['date'] === 'last7days'){
          $startDate = $currDate->subDays(7);
          $tempDate = CarbonPeriod::create($startDate->toDateTimeString(), Carbon::now()->toDateTimeString());
          foreach ($tempDate as $date) {
            array_push($dates, $date->toDateString());
          }
        }else{
          $month = $data['date'] === 'last_month' ? $currDate->subDays(30)->month : $currDate->month;
          $carbon = new Carbon(new Carbon(date('Y-m-d', strtotime('now', strtotime($currDate->year.'-' . $month . '-01'))), $this->response['timezone']));
          $i=0;
          while (intval($carbon->month) == intval($month)){
            $dates[$carbon->weekOfMonth][$i] = $carbon->toDateString();
            $carbon->addDay();
            $i++;
          }
        }
        if($data['date'] === 'yearly'){
          $temp = Account::select(DB::raw('COUNT(*) as total'),  DB::raw('YEAR(created_at) as year'))
            ->where($whereArray)
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->get();
          if(sizeof($temp) > 0){
            for ($i=0; $i <= sizeof($temp)-1 ; $i++) { 
              $item = $temp[$i];
              array_push($resDates, $item['year']);
              array_push($resData, $item['total']);
            }
          }
        }else if($data['date'] === 'current_year'){
          foreach ($dates as $key) {
            $temp = Account::where($whereArray)
              ->where('created_at', 'like', '%'.$currDate->year.'-'.$key.'%')->count();
            array_push($resDates, $key);
            array_push($resData, $temp);
          }
        }else if($data['date'] === 'last_month'){
          foreach ($dates as $key) {
            $temp = Account::where($whereArray)->whereBetween('created_at', [$key[array_key_first($key)], end($key)])->count();
            array_push($resDates, $key);
            array_push($resData, $temp);
          }
        }else if($data['date'] === 'current_month'){
          foreach ($dates as $key) {
            $temp = Account::where($whereArray)->whereBetween('created_at', [$key[array_key_first($key)], end($key)])->count();
            array_push($resDates, array_search($key, $dates));
            array_push($resData, $temp);
          }
        }else if($data['date'] === 'last7days'){
          $startDate = $currDate->subDays(7);
          foreach ($dates as $key) {
            $temp = Account::where($whereArray)->whereBetween('created_at', [$key, Carbon::now()->toDateTimeString()])->count();
            array_push($resDates, $key);
            array_push($resData, $temp);
          }
        }
        $this->response['data'] = array(
          'dates' => $resDates,
          'result' => $resData
        );
      }
      return $this->response();
    }
}