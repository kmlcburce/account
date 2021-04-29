<?php

namespace Increment\Account\Plan\Http;

use Increment\Account\Models\Plan\PlanHistory;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
class PlanHistoryController extends APIController
{
  function __construct(){
    $this->model = new PlanHistory();
  }
}