<?php

namespace App\Providers;

use App\Listeners\LogOtpMailSent;
use Illuminate\Mail\Events\MessageSent;
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
        // Listen for mail sent events and log OTP messages
        \Illuminate\Support\Facades\Event::listen(
            MessageSent::class,
            LogOtpMailSent::class
        );
    }
}
