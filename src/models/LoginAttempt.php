<?php

namespace Increment\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\APIModel;
class LoginAttempt extends APIModel
{
    protected $table = 'login_attempts';
    protected $fillable = ['account_id', 'value'];
}