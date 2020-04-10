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
}
