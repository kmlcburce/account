<?php

namespace Increment\Account\Http;

use Increment\Account\Models\AccountSetType;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
class AccountSetTypeController extends APIController
{
    function __construct(){
      $this->model = new AccountSetType();
    }

    public function retrieveByParams($column, $value){
      $result = AccountSetType::where($column, '=', $value)->get();
      return (sizeof($result) > 0) ? sizeof($result) : null;
    }

}