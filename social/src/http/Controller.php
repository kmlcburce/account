<?php

namespace Increment\Account\Social\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Increment\Account\Models\Account;
use Increment\Account\Models\AccountInformation;
use Increment\Account\Models\AccountProfile;

class Controller extends APIController
{
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

  public function generateCode(){
    $code = 'acc_'.substr(str_shuffle($this->codeSource), 0, 60);
    $codeExist = Account::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }


  public function auth(Request $request){
    $data = $request->all();

    if($data['payload'] == 'signup'){
      $dataAccount = array(
        'code'  => $this->generateCode(),
        'password'        => "",
        'status'          => isset($data['account_status']) ? $data['account_status'] :'NOT_VERIFIED',
        'email'           => $data['email'],
        'username'        => $data['username'],
        'account_type'    => $data['account_type'],
        'token'           => isset($data['token']) ? $data['token'] : null,
        'created_at'      => Carbon::now()
      );

      $this->model = new Account();
      $this->insertDB($dataAccount, true);      

      $id = $this->response['data'];
      
      if($id){
        if(isset($data['profile'])){
          $data['profile']['account_id'] = $id;
          $this->profile($data['profile']);
        }

        if(isset($data['merchant'])){
          $data['merchant']['account_id'] = $id;
          $this->profile($data['merchant']);
        }

        if(isset($data['information'])){
          $data['information']['account_id'] = $id;
          $this->profile($data['information']);
        }
      }      
    }else if($data['payload'] == 'signin'){
      $result = Account::whereRaw("BINARY email='".$data["username"]."' AND username='".$data['username']."'")->get();
      if($result){
        Account::where('id', '=', $result[0]['id'])->update(array(
            'token' => json_encode($data['token']),
            'updated_at' => Carbon::now()
          ));

        $this->response['data'] = true;
        $this->response['error'] = null;
      }else{
        $this->response['error'] = 'User not found';
        $this->response['data'] = null;
      }
    }


    return $this->response();
  }

  public function profile($data){
    $info = new AccountProfile();
    $info->account_id = $data['account_id'];
    $info->url = $data['url'];
    $info->created_at = Carbon::now();
    $info->save();
  }

  public function merchant($data){
    app('\Increment\Account\Merchant\Http\MerchantController')->createByParams(array(
      'account_id' => $data['account_id'],
      'name'  => $data['name']
    ));
  }

  public function information($data){
    $info = new AccountInformation();
    $info->account_id = $data['account_id'];
    $info->first_name = $data['first_name'];
    $info->last_name = $data['last_name'];
    $info->created_at = Carbon::now();
    $info->save();
  }
}
