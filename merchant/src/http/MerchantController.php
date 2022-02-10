<?php

namespace Increment\Account\Merchant\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Account\Merchant\Models\Merchant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MerchantController extends APIController
{

  function __construct()
  {
    $this->model = new Merchant();

    $this->notRequired = array(
      'name', 'address', 'prefix', 'logo', 'website', 'email', 'schedule', 'website', 'addition_informations'
    );
  }

  public function create(Request $request)
  {
    $data = $request->all();
    $verify = Merchant::where('account_id', '=', $data['account_id'])->get();
    if (count($verify) > 0) {
      array_push($this->response['error'], "Duplicate value for account id " . $data['account_id']);
      return $this->response();
    } else {
      $data['code'] = $this->generateCode();
      $data['status'] = 'not_verified';
      $this->model = new Merchant();
      $this->insertDB($data);
      return $this->response();
    }
  }

  public function generateCode()
  {
    $code = 'mer_' . substr(str_shuffle($this->codeSource), 0, 60);
    $codeExist = Merchant::where('id', '=', $code)->get();
    if (sizeof($codeExist) > 0) {
      $this->generateCode();
    } else {
      return $code;
    }
  }

  public function getByParams($column, $value)
  {
    $result = Merchant::where($column, '=', $value)->get();
    return sizeof($result) > 0 ? $result[0] : null;
  }

  public function retrieveWithFeaturedPhotos(Request $request) {
    $data = $request->all();
    $this->model = new Merchant();
    if(isset($data['masses'])) {
      $latitude = $data['masses']->latitude;
      $longitude = $data['masses']->longitude;
      $this->retrieveDB($data);
      $res= $this->response['data'];
      $l = 0;
      $result = array();
      if(sizeof($res) > 0) {
        foreach($res as $item) {
          $address = json_decode($res[$l]['address']);
          $lat = $address->latitude;
          $long = $address->longitude;
          $distance = app('Increment\Imarket\Location\Http\LocationController')->getLongLatDistance($latitude, $longitude, $lat, $long);
          $res[$l]['distance'] = $distance;
          if($distance <= 1) {
            array_push($result, $res[$l]);
          }
          $l++;
        }
      }
      $this->response['data'] = $result;
    } else {
      $this->retrieveDB($data);
      $result = $this->response['data'];
      $i = 0;
      if(sizeof($result) > 0) {
        foreach($result as $item) {
          $result[$i]['featured_photos'] = app('Increment\Common\Image\Http\ImageController')->retrieveFeaturedPhotos('account_id', $item['account_id'], 'category', 'featured-photo');
          $i++;
        }
      }
      $this->response['data'] = $result;
    }
    return $this->response();
  }
}
