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
          $this->response['data'][$i]['account_creator'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $i++;
        }
      }
      return $this->response();
    }

    public function retrieveByParams($column, $value){
      $result = SubAccount::where($column, '=', $value)->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['set_types'] = app('Increment\Account\Http\AccountSetTypeController')->retrieveByParams('account_id', $result[$i]['member']);
          $i++;
        }
      }
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function update(Request $request){
      $data = $request->all();
      // $password = $data['password'];
      $updateStatus = array(
        'id' => $data['id'],
        'status' => $data['status']
      );

      // app('Increment\Account\Http\AccountController')->updatePasswordViaSubAccount($data['account_id'], $password);
      $this->model = new SubAccount();
      $this->updateDB($updateStatus);
      return $this->response();
    }

}