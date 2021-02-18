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
