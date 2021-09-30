<?php

namespace Increment\Account\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Models\AccountProfile;
class AccountProfileController extends APIController
{
  function __construct(){
    $this->model = new AccountProfile();
  }

  public function getAccountProfile($accountId){
    $result = AccountProfile::where('account_id', '=', $accountId)->orderBy('created_at', 'desc')->get();
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getAllowedData($accountId){
    $result = AccountProfile::where('id', '=', $accountId)->get(['url']);
    return sizeof($result) > 0 ? $result[0] : null;
  }


  public function getProfileUrlByAccountId($accountId){
    $result = AccountProfile::where('account_id', '=', $accountId)->orderBy('created_at', 'desc')->get(['url']);
    return sizeof($result) > 0 ? $result[0] : null;
  }


  public function getByParamsWithColumns($accountId, $columns){
    $result = AccountProfile::where('account_id', '=', $accountId)->get($columns);
    return (sizeof($result) > 0) ? $result[0] : null;
  }

}
