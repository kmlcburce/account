<?php

namespace Increment\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class AccountOnline extends APIModel
{
    protected $table = 'account_onlines';
    protected $fillable = ['account_id', 'status'];

    public function getAccountIdAttribute($value){
      return intval($value);
    }

    public function getStatusAttribute($value){
      return intval($value);
    }
}