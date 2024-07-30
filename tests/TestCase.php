<?php

namespace RahulHaque\Filepond\Tests;

use RahulHaque\Filepond\FilepondServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [FilepondServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Filepond' => \RahulHaque\Filepond\Facades\Filepond::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        include_once __DIR__.'/../database/migrations/create_fileponds_table.php.stub';
        include_once __DIR__.'/database/migrations/create_users_table.php.stub';

        (new \CreateUsersTable)->up();
        (new \CreateFilepondsTable)->up();
    }
}
