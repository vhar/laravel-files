<?php

namespace Vhar\LaravelFiles;

use Illuminate\Support\ServiceProvider;
use Vhar\LaravelFiles\Services\FileService;

class FileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('laravel-files', function ($app) {
            return new FileService();
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../config/files.php',
            'files'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_files_table.php.stub'
                => database_path('migrations/' . date('Y_m_d_His') . '_create_files_table.php'),

                __DIR__ . '/../database/migrations/create_fileables_table.php.stub'
                => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_fileables_table.php'),
            ], 'laravel-files-migrations');

        }
    }
}