<?php

namespace Increment\Account\Plan\Http;

use Increment\Account\Plan\Models\\Plan;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
class PlanController extends APIController
{
  function __construct(){
    $this->model = new Plan();
    $this->notRequired = array('merchant_id');
  }

  public function create(Request $request){
    $data = $request->all();
    $this->model = new Plan();
    $data['code'] = $this->generateCode();
    $this->insertDB($data);
    return $this->response();
  }

  public function generateCode(){
    $code = 'pln_'.substr(str_shuffle($this->codeSource), 0, 60);
    $codeExist = Plan::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }
}