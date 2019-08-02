<?php

namespace Increment\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class Account extends APIModel
{
    protected $table = 'sub_accounts';
    protected $fillable = ['account_id', 'member', 'status'];
}