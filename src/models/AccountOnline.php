<?php

namespace Increment\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class AccountOnlines extends APIModel
{
    protected $table = 'account_onlines';
    protected $fillable = ['account_id', 'status'];
}