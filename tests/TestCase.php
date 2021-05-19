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
    return [
      'Filepond' => \RahulHaque\Filepond\Facades\Filepond::class,
    ];
  }

  protected function getEnvironmentSetUp($app)
  {
    // perform environment setup
  }
}
