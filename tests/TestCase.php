<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, InteractsWithAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        // Force array session + disable email verification requirement for tests if needed
        config([
            'session.driver' => 'array',
        ]);
    }
}
