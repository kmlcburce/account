<?php

namespace Increment\Account\Merchant;

use Illuminate\Support\ServiceProvider;

class MerchantServiceProvider extends ServiceProvider{

  public function boot(){
    $this->loadMigrationsFrom(__DIR__.'/migrations');
    $this->loadRoutesFrom(__DIR__.'/routes/web.php');
  }

  public function register(){
  }
}