<?php

namespace Increment\Account\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Models\AccountOnline;
class AccountOnlineController extends APIController
{
  function __construct(){
    $this->model = new AccountOnline();
  }

  public function create(Request $request){
    $data = $request->all();

    if($this->getAccountStatus($data['account_id']) != null){
      // update
      $this->model = new AccountOnline();
      $array = array(
        'id' => $data['id'],
        'status' => $data['status']
      );
      $this->updateDB($data);
      return $this->response();
    }else{
      $this->model = new AccountOnline();
      $this->insertDB($data);
      return $this->response();
    }
  }

  public function getAccountStatus($accountId){
    $result = AccountOnline::where('account_id', '=', $accountId)->get();
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getStatus($accountId){
    $result = AccountOnline::where('account_id', '=', $accountId)->get();
    if(sizeof($result) > 0){
      return (intval($result[0]['status']) == 1) ? true : false;
    }
    return false;
  }

}
