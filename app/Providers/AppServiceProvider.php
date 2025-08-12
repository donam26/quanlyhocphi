<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Đăng ký Repository Pattern
        $this->app->bind(
            \App\Contracts\StudentRepositoryInterface::class,
            \App\Repositories\StudentRepository::class
        );

        $this->app->bind(
            \App\Contracts\PaymentRepositoryInterface::class,
            \App\Repositories\PaymentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        // Đặt định dạng ngày tháng mặc định là dd/mm/yyyy
        Carbon::setToStringFormat(config('app.date_format', 'd/m/Y'));
        
        // Đặt ngôn ngữ cho Carbon là tiếng Việt
        Carbon::setLocale('vi');
    }
}
