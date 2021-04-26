<?php

namespace Increment\Account\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Models\AccountInformation;
use Carbon\Carbon;
class AccountInformationController extends APIController
{

  function __construct(){
    $this->localization();
    $this->model = new AccountInformation();
    $this->notRequired = array(
      'sex', 'birth_date', 'cellular_number', 'address'
    );
  }

  public function update(Request $request){
    $data = $request->all();
    if($this->checkIfExist($data['account_id']) == true){
      $this->model = new AccountInformation();
      $this->updateDB($data);
      return $this->response();
    }else{
      $this->model = new AccountInformation();
      $this->insertDB($data);
      return $this->response();
    }
  }

  public function retrieveAccountInfo(Request $request){
    $data = $request->all();
    $result = AccountInformation::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])->get();
    $i = 0;
    foreach ($result as $key) {
      $account = app('Increment\Account\Http\AccountController')->getAllowedData($data['condition'][0]['value']);
      $result[$i]['profile'] = app('Increment\Account\Http\AccountProfileController')->getByParamsWithColumns($data['condition'][0]['value'], ['url', 'id']);
      $result[$i]['username'] = $account['username'];
      $result[$i]['status'] = $account['status'];
      $result[$i]['rating'] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload('account', $result[$i]['account_id']);
    }
    $this->response['data'] = $result;

    return $this->response();
  }

  public function checkIfExist($accountId){
    $result = AccountInformation::where('account_id', '=', $accountId)->get();
    if(sizeof($result) > 0){
      return true;
    }else{
      return false;
    }
  }

  public function getAccountInformation($accountId){
    $result = AccountInformation::where('account_id', '=', $accountId)->get();
    if(sizeof($result) > 0){
      $result[0]['birth_date_human'] = ($result[0]['birth_date'] != null && $result[0]['birth_date'] != '') ?Carbon::createFromFormat('Y-m-d', $result[0]['birth_date'])->copy()->tz($this->response['timezone'])->format('F j, Y') : null;
    }
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getByParamsWithColumns($accountId, $columns){
    $result = AccountInformation::where('account_id', '=', $accountId)->get($columns);
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getAllowedData($accountId){
    $result = AccountInformation::where('id', '=', $accountId)->get(['first_name', 'last_name', 'middle_name', 'sex']);
    return sizeof($result) > 0 ? $result[0] : null;
  }
}
