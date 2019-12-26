<?php

namespace Increment\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class SubAccount extends APIModel
{
  protected $table = 'sub_accounts';
  protected $fillable = ['account_id', 'member', 'status'];

  public function getAccountIdAttribute($value){
    return intval($value);
  }

  public function getMemberAttribute($value){
    return intval($value);
  }
}