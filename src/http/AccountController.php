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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountController extends APIController
{

  public $accountCardController = 'App\Http\Controllers\AccountCardController';

    function __construct(){
      $this->model = new Account();
      $this->validation = array(  
        "email" => "unique:accounts",
        "username"  => "unique:accounts"
      );
      $this->notRequired = array(
        'token',
        "password"
      );
    }

    public function create(Request $request){
     $request = $request->all();
     $referralCode = $request['referral_code'];
     $invitationPassword = $request['password'];
     $dataAccount = array(
      'code'  => $this->generateCode(),
      'password'        => $request['password'] !== null ? Hash::make($request['password']) : "",
      'status'          => 'NOT_VERIFIED',
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
        app('App\Http\Controllers\EmailController')->verification($this->response['data'][0]['id']);  
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
        }
        app('App\Http\Controllers\EmailController')->verification_status($data['id'], $details);
      }
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

    public function getAccountIdByParamsWithColumns($code, $columns){
      $result = Account::where('code', '=', $code)->get($columns);
      return (sizeof($result) > 0) ? $result[0] : null;
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
    
    public function updateTokenByEmail(Request $request){
      $data = $request->all();
      $exist = Account::where('email', '=', $data['email'])->get();
      if(sizeof($exist) > 0){
        $result = Account::where('email', '=', $data['email'])->update(array(
          'token' => $data['token']
        ));
        $this->response['data'] = $result;
      }else{
        $this->response['data'] = null;
        $this->response['error'] = 'Email does not exist';
      }
      return $this->response();
    }
}