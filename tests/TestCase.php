<?php

namespace Depsimon\Wallet\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->withFactories(__DIR__ . '/factories');
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom('database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            'Depsimon\Wallet\WalletServiceProvider'
        ];
    }
}
