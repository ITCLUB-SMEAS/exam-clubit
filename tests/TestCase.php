<?php

namespace Tests;

use App\Http\Middleware\ValidateTurnstile;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bypass Turnstile validation in tests
        $this->withoutMiddleware(ValidateTurnstile::class);
    }
}
