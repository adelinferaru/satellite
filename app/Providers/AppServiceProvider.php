<?php

namespace App\Providers;

use App\Repositories\ISSContract;
use App\Repositories\ISSGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ISSContract::class, ISSGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
