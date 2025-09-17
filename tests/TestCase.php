<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [\RahulHaque\Filepond\FilepondServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Filepond' => \RahulHaque\Filepond\Facades\Filepond::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app->call([require __DIR__.'/../database/migrations/create_fileponds_table.php.stub', 'up']);
    }
}
