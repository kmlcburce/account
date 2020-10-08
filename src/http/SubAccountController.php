<?php

namespace Increment\Account\Http;

use Increment\Account\Models\SubAccount;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
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
      if(sizeof($data['condition']) > 0){
        $this->response['size'] = SubAccount::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])->count();
      }
      
      return $this->response();
    }

    public function retrieveAll(Request $request) {
      $data = $request->all();
      $con = $data['condition'];
      $results = array();
      $name = null;
      if($con[0]['value'] != '%') {
        $name = DB::table('sub_accounts as T1')
          ->join('accounts as T2', 'T1.member', '=', 'T2.id')
          ->Where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
          ->Where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
          ->WhereNull('T1.deleted_at')
          ->select('T2.username', 'T2.email', 'T1.status', 'T1.account_id', 'T1.id', 'T1.member', 'T1.created_at', 'T1.updated_at', 'T1.deleted_at')
          ->skip($data['offset'])
          ->take($data['limit'])
          ->orderBy($con[0]['column'], $data['sort'][$con[0]['column']])
          ->get();

          $this->response['size'] =DB::table('sub_accounts as T1')
            ->join('accounts as T2', 'T1.member', '=', 'T2.id')
            ->Where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
            ->Where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
            ->WhereNull('T1.deleted_at')
            ->count();

      } else {
        $name = DB::table('sub_accounts as T1')
        ->join('accounts as T2', 'T1.member', '=', 'T2.id')
        ->Where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
        ->WhereNull('T1.deleted_at')
        ->select('T2.username', 'T2.email', 'T1.status', 'T1.account_id', 'T1.id', 'T1.member', 'T1.created_at', 'T1.updated_at', 'T1.deleted_at')
        ->skip($data['offset'])
        ->take($data['limit'])
        ->orderBy($con[0]['column'], $data['sort'][$con[0]['column']])
        ->get();

        $this->response['size'] = DB::table('sub_accounts as T1')
        ->join('accounts as T2', 'T1.member', '=', 'T2.id')
        ->Where($con[1]['column'], $con[1]['clause'], $con[1]['value'])
        ->WhereNull('T1.deleted_at')
        ->count();

      }
      $i = 0;
      foreach ($name as $key) {
        $results[$i]['updated_at'] = $key->updated_at;
        $results[$i]['created_at'] = $key->created_at;
        $results[$i]['deleted_at'] = $key->deleted_at;
        $results[$i]['status'] = $key->status;
        $results[$i]['id'] = $key->id;
        $results[$i]['account_id'] = $key->account_id;
        $results[$i]['member'] = $key->member;
        $results[$i]['account'] = $this->retrieveAccountDetails($key->member);
        $results[$i]['account_creator'] = $this->retrieveAccountDetails($key->account_id);
        $i++;
      }
      
      $this->response['data'] = $results;

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