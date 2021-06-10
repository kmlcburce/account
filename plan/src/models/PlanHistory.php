<?php

namespace Increment\Account\Plan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class PlanHistory extends APIModel
{
    protected $table = 'plan_histories';
    protected $fillable = ['code', 'account_id', 'plan_id', 'amount', 'currency', 'status'];

    public function getAccountIdAttribute($value){
      return intval($value);
    }

}