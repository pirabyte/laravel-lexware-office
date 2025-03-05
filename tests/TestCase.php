<?php

namespace Pirabyte\LaravelLexwareOffice\Tests;

use Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * @method setUp()
 */
class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LexwareOfficeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('lexware-office.base_url', 'https://test-api.lexoffice.de/v1');
        $app['config']->set('lexware-office.api_key', 'test-api-key');
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mockery für die Facade im Test vorbereiten
        $this->withoutMockingConsoleOutput();
    }
}