<?php

namespace Increment\Account\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Models\LoginAttempt;
class LoginAttemptController extends APIController
{
    function __construct(){
      $this->model = new LoginAttempt();
    }

    public function update($data){
      if($this->getStatus('account_id', $data['account_id']) == null){
        $this->insertDB($data);
      }else{
        $this->updateDB($data);
      }
      return true;
    }

    public function getStatus($column, $value){
      $result = LoginAttempt::where($column, '=', $value)->get();

      if(sizeof($result) > 0){
        if(intval($result[0]['value']) == intval(env('LOGIN_ATTEMPT_LIMIT'))){
          return null;
        }else{
          return intval($result[0]['value']) + 1;
        }
      }
      return 1;
    }
}
