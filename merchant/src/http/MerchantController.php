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
}
