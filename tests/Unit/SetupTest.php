<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class SetupTest extends TestCase
{
    public function test_it_can_setup_package()
    {
        $this->assertTrue(class_exists(LexwareOffice::class));
        $this->assertTrue(class_exists(LexwareOfficeServiceProvider::class));
    }

    public function test_it_can_register_services()
    {
        $instance = $this->app->make('lexware-office');
        $this->assertInstanceOf(LexwareOffice::class, $instance);
    }
}
