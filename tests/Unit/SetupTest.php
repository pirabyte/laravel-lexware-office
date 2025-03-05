<?php

namespace Pirabyte\LaravelLexwareOffice\Tests\Unit;

use Pirabyte\LaravelLexwareOffice\LexwareOffice;
use Pirabyte\LaravelLexwareOffice\LexwareOfficeServiceProvider;
use Pirabyte\LaravelLexwareOffice\Tests\TestCase;

class SetupTest extends TestCase
{
    /** @test */
    public function the_package_can_be_instantiated()
    {
        $this->assertTrue(class_exists(LexwareOffice::class));
        $this->assertTrue(class_exists(LexwareOfficeServiceProvider::class));
    }

    /** @test */
    public function the_service_is_registered()
    {
        $instance = $this->app->make('lexware-office');
        $this->assertInstanceOf(LexwareOffice::class, $instance);
    }
}