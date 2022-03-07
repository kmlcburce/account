<?php

namespace Increment\Account\Social\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Controller extends APIController
{
  function __construct(){
  }

  public function create(Request $request){
    $data = $request->all();
  }

}
