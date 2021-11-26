<?php

namespace Increment\Account\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Models\AccountInformation;
use Increment\Common\Payload\Models\Payload;
use Carbon\Carbon;
class AccountInformationController extends APIController
{


  public $cacheController = 'Increment\Common\Cache\Http\CacheController';
  function __construct(){
    $this->localization();
    $this->model = new AccountInformation();
    $this->notRequired = array(
      'sex', 'birth_date', 'cellular_number', 'address'
    );
  }

  public function createWithLocation(Request $request){
    $data = $request->all();
    if($this->checkIfExist($data['account_id']) == true){
      $this->model = new AccountInformation();
      $array = array(
        'account_id' => $data['account_id'],
        'address' => $data['address']
      );
      $this->updateDB($data);

      $allAddress = AccountInformation::leftJoin('accounts as T1', 'T1.id', '=', 'account_informations.account_id')->leftJoin('merchants as T2', 'T2.account_id', '=', 'T1.id')
        ->where('T1.deleted_at', '=', NULL)->where('account_informations.address', '!=', 'null')->where('T1.id', '!=', NULL)->get(['account_informations.address', 'T1.id', 'T2.addition_informations']);
      $allAdd = json_decode($allAddress);

      $i = 0;
      foreach($allAdd as $key){
        $checkLocationIfExist = app('Increment\Imarket\Location\Http\LocationController')->getLongLatDistance(json_decode($data['address'])->latitude, json_decode($data['address'])->longitude, json_decode($allAdd[$i]->address)->latitude, json_decode($allAdd[$i]->address)->longitude);
        $distance = number_format($checkLocationIfExist * 0.62137119, 2);
        if((int)$distance <= env('DISTANCE')){
          // CHECK IF EXIST
          $check = $this->checkIfAccountIdExist(json_decode($allAdd[$i]->id));
          if($check === false){
            $a=0;
            $exist = $size = Payload::where('payload', '=', 'competitor')->where('payload_value', 'like', '%"locality":"'.json_decode($allAdd[$i]->address)->locality.'"')->where('category', '=', json_decode($allAdd[$i]->addition_informations)->industry)->get();
            if(sizeof($exist) > 0){
              foreach($exist as $ndx){
                $payload = new Payload();
                $payload->account_id = json_decode($allAdd[$i]->id);
                $payload->payload = 'competitor';
                $payload->category = json_decode($allAdd[$i]->addition_informations)->industry;
                $payload->payload_value = json_encode(array('locality' => json_decode($allAdd[$i]->address)->locality, 'rank' => (int)sizeof($size) + 1));
                $payload->created_at = Carbon::now();
                $payload->save();
                $a++;
              }
            }else{
              $payload = new Payload();
              $payload->account_id = json_decode($allAdd[$i]->id);
              $payload->payload = 'competitor';
              $payload->category = json_decode($allAdd[$i]->addition_informations)->industry;
              $payload->payload_value = json_encode(array('locality' => json_decode($allAdd[$i]->address)->locality, 'rank' => (int)sizeof($size) + 1));
              $payload->created_at = Carbon::now();
              $payload->save();
              $a++;
            }
          }
        }
        $i++;
      }
      return $this->response();
    }else{
      $this->model = new AccountInformation();
      $this->insertDB($data);
      return $this->response();
    }
  }

  public function checkIfAccountIdExist($id){
    $count = Payload::where('account_id', '=', $id)->where('payload', '=', 'competitor')->count();
    if($count >= 1){
      return true;
    }else{
      return false;
    }
  }

  public function update(Request $request){
    $data = $request->all();
    if($this->checkIfExist($data['account_id']) == true){
      $this->model = new AccountInformation();
      $this->updateDB($data);
      app($this->cacheController)->delete('user_'.$data['account_id']);
      app($this->cacheController)->delete('account_informations_'.$data['account_id']);
      app($this->cacheController)->delete('account_details_'.$data['account_id']);
      return $this->response();
    }else{
      $this->model = new AccountInformation();
      $this->insertDB($data);
      app($this->cacheController)->delete('user_'.$data['account_id']);
      app($this->cacheController)->delete('account_informations_'.$data['account_id']);
      app($this->cacheController)->delete('account_details_'.$data['account_id']);
      return $this->response();
    }
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $accountId = null;
    $limit = null;
    $offset = null;

    foreach ($data['condition'] as $key) {
      if($key['column'] === 'account_id'){
        $accountId = $key['value'];
      }
    }

    if(isset($data['limit'])){
      $limit = intval($data['limit']);
    }


    if(isset($data['offset'])){
      $offset = intval($data['offset']);
    }

    $result = app($this->cacheController)->retrieve('account_informations_'.$accountId, $offset, $limit);

    if(app($this->cacheController)->retrieveCondition($result, $offset) == true){
      $this->response['data'] = $result;
    }else{
      $this->model = new AccountInformation();
      $this->retrieveDB($data);
      app($this->cacheController)->insert('account_informations_'.$accountId, $this->response['data']);
    }

    return $this->response();
  }

  public function retrieveAccountInfo(Request $request){
    $data = $request->all();
    $result = AccountInformation::where($data['condition'][0]['column'], $data['condition'][0]['clause'], $data['condition'][0]['value'])->get();
    $i = 0;
    foreach ($result as $key) {
      $account = app('Increment\Account\Http\AccountController')->getAllowedData($data['condition'][0]['value']);
      $result[$i]['profile'] = app('Increment\Account\Http\AccountProfileController')->getByParamsWithColumns($data['condition'][0]['value'], ['url', 'id']);
      $result[$i]['username'] = $account['username'];
      $result[$i]['status'] = $account['status'];
      $result[$i]['rating'] = app('Increment\Common\Rating\Http\RatingController')->getRatingByPayload('account', $result[$i]['account_id']);
    }
    $this->response['data'] = $result;

    return $this->response();
  }

  public function checkIfExist($accountId){
    $result = AccountInformation::where('account_id', '=', $accountId)->get();
    if(sizeof($result) > 0){
      return true;
    }else{
      return false;
    }
  }

  public function getAccountInformation($accountId){
    $result = AccountInformation::where('account_id', '=', $accountId)->get();
    if(sizeof($result) > 0){
      $result[0]['birth_date_human'] = ($result[0]['birth_date'] != null && $result[0]['birth_date'] != '') ?Carbon::createFromFormat('Y-m-d', $result[0]['birth_date'])->copy()->tz($this->response['timezone'])->format('F j, Y') : null;
    }
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getByParamsWithColumns($accountId, $columns){
    $result = AccountInformation::where('account_id', '=', $accountId)->get($columns);
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getAllowedData($accountId){
    $result = AccountInformation::where('id', '=', $accountId)->get(['first_name', 'last_name', 'middle_name', 'sex']);
    return sizeof($result) > 0 ? $result[0] : null;
  }
}
