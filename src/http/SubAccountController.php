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

    public function createByParams($accountId, $member, $status){
      $model = new SubAccount();
      $model->account_id = $accountId;
      $model->member = $member;
      $model->status = $status;
      $model->created_at = Carbon::now();
      $model->save();
      return true;
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new SubAccount();
      $this->retrieveDB($data);
      $result = $this->response['data'];

      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['member']);
          $i++;
        }
      }
      return $this->response();
    }

}