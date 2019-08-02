<?php

namespace Increment\Account\Http;

use Increment\Account\Models\SubAccount;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
class SubAccountController extends APIController
{
    function __construct(){
      $this->model = new SubAccount();
    }

}