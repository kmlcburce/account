<?php

namespace Increment\Account\Plan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class Plan extends APIModel
{
    protected $table = 'plans';
    protected $fillable = ['code', 'account_id', 'merchant_id', 'plan', 'amount', 'currency', 'status'];

    public function getAccountIdAttribute($value){
      return intval($value);
    }

}