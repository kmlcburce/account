<?php

namespace Increment\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class AccountSetType extends APIModel
{
    protected $table = 'account_set_types';
    protected $fillable = ['account_id'];

    public function getAccountIdAttribute($value){
      return intval($value);
    }

}