<?php

namespace App\Providers;

use App\Models\DailyPaymentRequest;
use App\Observers\DailyPaymentRequestObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DailyPaymentRequest::observe(DailyPaymentRequestObserver::class);
    }
}
