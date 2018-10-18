<?php

namespace Depsimon\Wallet;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class WalletServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('wallet.php'),
            ], 'config');
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__ . '/../database/migrations/2018_09_13_123456_create_wallet_tables.php' => database_path('migrations/' . $timestamp . '_create_wallet_tables.php'),
            ], 'migrations');
        }
        if (config('wallet.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
        $this->app->make(Factory::class)->load(__DIR__ . '/../database/factories');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'wallet');
    }
}
