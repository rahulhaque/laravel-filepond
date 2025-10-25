<?php

declare(strict_types=1);

namespace RahulHaque\Filepond;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;
use RahulHaque\Filepond\Console\FilepondClear;
use RahulHaque\Filepond\Factories\UploaderManager;
use RahulHaque\Filepond\Rules\FilepondRule;

class FilepondServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Rule::macro('filepond', function ($args) {
            return new FilepondRule($args);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filepond.php' => base_path('config/filepond.php'),
            ], 'filepond-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_fileponds_table.php.stub' => glob(database_path('/migrations/*_create_fileponds_table.php'))[0] ?? database_path('migrations/'.date('Y_m_d_His', time()).'_create_fileponds_table.php'),
            ], 'filepond-migrations');

            $this->commands([
                FilepondClear::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filepond.php', 'filepond');

        $this->app->singleton(UploaderManager::class, fn (Application $app) => new UploaderManager($app));

        $this->app->singleton('filepond', fn () => new Filepond);
    }
}
