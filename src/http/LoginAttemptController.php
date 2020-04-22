<?php

namespace Increment\Account\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Models\LoginAttempt;
use Carbon\Carbon;
class LoginAttemptController extends APIController
{
    function __construct(){
      $this->model = new LoginAttempt();
    }

    public function updateData($data){
      $attempt = $this->getByParams('account_id', $data['account_id']);
      // echo 'hi';
      if($attempt == null){
        $data['value'] = 1;
        $this->insertDB($data);
      }else{
        if(intval($attempt['value']) <= intval(env('LOGIN_ATTEMPT_LIMIT'))){
          LoginAttempt::where('account_id', '=', $data['account_id'])
          ->update(array(
            'value' => $attempt['value'],
            'updated_at' => Carbon::now()
          ));
        }
      }
      return true;
    }

    public function reset($data){
      return LoginAttempt::where('account_id', '=', $data['account_id'])
      ->update(array(
        'value' => 0,
        'updated_at' => Carbon::now()
      ));
    }

    public function getByParams($column, $value){
      $result = LoginAttempt::where($column, '=', $value)->get();

      if(sizeof($result) > 0){
        if(intval($result[0]['value']) == intval(env('LOGIN_ATTEMPT_LIMIT'))){
          app('App\Http\Controllers\EmailController')->accountLocked($result[0]['account_id']);
        }
        $result[0]['value'] = intval($result[0]['value']) + 1;
      }

      return sizeof($result) > 0 ? $result[0] : null;
    }

    public function getStatus($column, $value){
      $result = LoginAttempt::where($column, '=', $value)->get();
      if(sizeof($result) > 0){
        if(intval($result[0]['value']) >= intval(env('LOGIN_ATTEMPT_LIMIT'))){
          return false;
        }
      }
      return true;
    }
}
