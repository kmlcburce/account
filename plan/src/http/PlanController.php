<?php

namespace Increment\Account\Plan\Http;

use Increment\Account\Plan\Models\Plan;
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

  public function retrieve(Request $request){
    $data = $request->all();
    $this->model = new Plan();
    $this->retrieveDB($data);
    $result = $this->response['data'];

    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
        $this->response['data'][$i]['merchant'] = null;
        if($result[$i]['merchant_id']){
          $this->response['data'][$i]['merchant'] = app('Increment\Imarket\Merchant\Http\MerchantController')->getByParams('id', $result[$i]['merchant_id']);
        }

        $i++;
      }
    }
    return $this->response();
  }

  public function getByParams($column, $value){
    $result = Plan::where($column, '=', $value)->orderBy('created_at', 'desc')->limit(1)->get();
    return sizeof($result) > 0 ? $result[0] : null;
  }
}