<?php

namespace Increment\Account\Merchant\Models;
use Illuminate\Database\Eloquent\Model;
use App\APIModel;
class Merchant extends APIModel
{
    protected $table = 'merchants';
    protected $fillable = ['code', 'account_id', 'email', 'name', 'prefix', 'logo', 'address', 'status', 'schedule', 'website'];
}
